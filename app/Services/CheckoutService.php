<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
        protected ?StorePricingService $pricingService = null
    ) {}

    public function prepare(Cart $cart): array
    {
        $cart->load('items.product');
        $priceChanges = [];
        $availabilityChanges = [];

        foreach ($cart->items as $item) {
            $isAvailable = $this->inventoryService->isAvailable($item->product);

            if (! $isAvailable) {
                $oldPrice = (float) $item->unit_price;
                if ($oldPrice != 0) {
                    $item->update(['unit_price' => 0]);
                    $availabilityChanges[] = [
                        'type' => 'out_of_stock', 'item_id' => $item->id,
                        'cart_item_id' => $item->id, 'product_id' => $item->product_id,
                        'product_name' => $item->product->name, 'old_price' => $oldPrice, 'new_price' => 0,
                    ];
                }

                continue;
            }

            $currentPrice = $this->cartService->getCurrentPrice($item->product, (int) $item->quantity);
            if (! $currentPrice) {
                $oldPrice = (float) $item->unit_price;
                if ($oldPrice != 0) {
                    $item->update(['unit_price' => 0]);
                    $availabilityChanges[] = [
                        'type' => 'unavailable', 'item_id' => $item->id,
                        'cart_item_id' => $item->id, 'product_id' => $item->product_id,
                        'product_name' => $item->product->name, 'old_price' => $oldPrice, 'new_price' => 0,
                    ];
                }

                continue;
            }

            $oldPrice = (float) $item->unit_price;
            $newPrice = (float) $currentPrice->price;
            if ($oldPrice == 0) {
                $item->update(['unit_price' => $currentPrice->price]);

                continue;
            }

            if ($oldPrice !== $newPrice) {
                $item->update(['unit_price' => $currentPrice->price]);
                $priceChanges[] = [
                    'type' => 'price_changed', 'item_id' => $item->id,
                    'cart_item_id' => $item->id, 'product_id' => $item->product_id,
                    'product_name' => $item->product->name, 'old_price' => $oldPrice, 'new_price' => $newPrice,
                ];
            }
        }

        $cart->refresh();
        $cart->load('items.product');
        $changes = array_merge($priceChanges, $availabilityChanges);
        $subtotal = $this->cartService->calculateSubtotal($cart);
        $shippingAmount = $this->pricing()->calculateShipping($subtotal);
        $minimumOrderAmount = $this->pricing()->minimumOrderAmount();
        $minimumOrderMet = $minimumOrderAmount <= 0 || $subtotal >= $minimumOrderAmount;

        return [
            'cart' => $cart,
            'changes' => $changes,
            'price_changes' => $priceChanges,
            'availability_changes' => $availabilityChanges,
            'requires_price_confirmation' => count($changes) > 0,
            'subtotal' => $subtotal,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $subtotal + $shippingAmount,
            'minimum_order_amount' => $minimumOrderAmount,
            'minimum_order_met' => $minimumOrderMet,
            'can_proceed_to_payment' => $subtotal > 0 && $minimumOrderMet,
            'cart_has_payable_items' => $subtotal > 0,
        ];
    }

    public function confirmPriceChanges(Cart $cart): array
    {
        return DB::transaction(function () use ($cart) {
            $cart->load('items.product');
            $outOfStockItems = [];

            foreach ($cart->items as $item) {
                if (! $this->inventoryService->isAvailable($item->product)) {
                    $outOfStockItems[] = $item;
                }
            }

            $priceChanges = [];
            foreach ($cart->items as $item) {
                if (! $this->inventoryService->isAvailable($item->product)) {
                    continue;
                }

                $currentPrice = $this->cartService->getCurrentPrice($item->product, (int) $item->quantity);
                if (! $currentPrice) {
                    continue;
                }

                $oldPrice = (float) $item->unit_price;
                $newPrice = (float) $currentPrice->price;
                if ($oldPrice == 0) {
                    $item->update(['unit_price' => $currentPrice->price]);

                    continue;
                }

                if ($oldPrice !== $newPrice) {
                    $priceChanges[] = [
                        'item_id' => $item->id, 'product_id' => $item->product_id,
                        'product_name' => $item->product->name, 'old_price' => $oldPrice,
                        'new_price' => $newPrice, 'type' => 'price_changed',
                    ];
                }
            }

            if (count($priceChanges) > 0) {
                throw new RuntimeException('قیمت سبد خرید دوباره تغییر کرده است. لطفاً قیمت‌های جدید را بررسی و تأیید کنید.');
            }

            foreach ($outOfStockItems as $item) {
                $item->delete();
            }

            $cart->refresh();
            $cart->load('items.product');
            $subtotal = $this->cartService->calculateSubtotal($cart);
            $minimumOrderAmount = $this->pricing()->minimumOrderAmount();
            $minimumOrderMet = $minimumOrderAmount <= 0 || $subtotal >= $minimumOrderAmount;

            if ($subtotal <= 0) {
                return [
                    'cart' => $cart, 'confirmed' => true, 'payment_allowed' => false,
                    'subtotal' => 0, 'shipping_amount' => 0, 'total_amount' => 0,
                    'minimum_order_amount' => $minimumOrderAmount,
                    'minimum_order_met' => false,
                    'message' => 'هیچ کالای قابل خریدی در سبد شما باقی نمانده است.',
                ];
            }

            $shippingAmount = $this->pricing()->calculateShipping($subtotal);

            if (! $minimumOrderMet) {
                return [
                    'cart' => $cart, 'confirmed' => true, 'payment_allowed' => false,
                    'subtotal' => $subtotal, 'shipping_amount' => $shippingAmount,
                    'total_amount' => $subtotal + $shippingAmount,
                    'minimum_order_amount' => $minimumOrderAmount,
                    'minimum_order_met' => false,
                    'message' => 'حداقل مبلغ سفارش '.number_format($minimumOrderAmount, 0, '.', ',').' تومان است.',
                ];
            }

            return [
                'cart' => $cart, 'confirmed' => true, 'payment_allowed' => true,
                'subtotal' => $subtotal, 'shipping_amount' => $shippingAmount,
                'total_amount' => $subtotal + $shippingAmount,
                'minimum_order_amount' => $minimumOrderAmount,
                'minimum_order_met' => true,
            ];
        });
    }

    public function rejectPriceChanges(Cart $cart): array
    {
        $cart->refresh();
        $cart->load('items.product');
        $subtotal = $this->cartService->calculateSubtotal($cart);
        $shippingAmount = $subtotal > 0 ? $this->pricing()->calculateShipping($subtotal) : 0;
        $minimumOrderAmount = $this->pricing()->minimumOrderAmount();
        $minimumOrderMet = $minimumOrderAmount <= 0 || $subtotal >= $minimumOrderAmount;

        return [
            'cart' => $cart, 'confirmed' => false, 'payment_allowed' => false,
            'subtotal' => $subtotal, 'shipping_amount' => $shippingAmount,
            'total_amount' => $subtotal + $shippingAmount,
            'minimum_order_amount' => $minimumOrderAmount,
            'minimum_order_met' => $minimumOrderMet,
        ];
    }

    protected function pricing(): StorePricingService
    {
        return $this->pricingService ?? app(StorePricingService::class);
    }
}
