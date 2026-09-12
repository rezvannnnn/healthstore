<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Prepare cart for checkout.
     *
     * Checks:
     * - current product availability
     * - current product price
     *
     * An unavailable product temporarily gets a zero price.
     * If that product becomes available again later, its current
     * price is restored without being treated as a real price change.
     */
    public function prepare(Cart $cart): array
    {
        $cart->load('items.product');

        $priceChanges = [];
        $availabilityChanges = [];

        foreach ($cart->items as $item) {
            $isAvailable = $this->inventoryService->isAvailable(
                $item->product
            );

            /*
             * Product is currently unavailable.
             *
             * Keep it in the cart temporarily with zero price.
             */
            if (! $isAvailable) {
                $oldPrice = (float) $item->unit_price;

                if ($oldPrice != 0) {
                    $item->update([
                        'unit_price' => 0,
                    ]);

                    $availabilityChanges[] = [
                        'type' => 'out_of_stock',
                        'item_id' => $item->id,
                        'cart_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'old_price' => $oldPrice,
                        'new_price' => 0,
                    ];
                }

                continue;
            }

            /*
             * Product is available again.
             */
            $currentPrice = $this->cartService->getCurrentPrice(
                $item->product
            );

            /*
             * No active price means the product cannot currently
             * be sold even if inventory exists.
             */
            if (! $currentPrice) {
                $oldPrice = (float) $item->unit_price;

                if ($oldPrice != 0) {
                    $item->update([
                        'unit_price' => 0,
                    ]);

                    $availabilityChanges[] = [
                        'type' => 'unavailable',
                        'item_id' => $item->id,
                        'cart_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'old_price' => $oldPrice,
                        'new_price' => 0,
                    ];
                }

                continue;
            }

            $oldPrice = (float) $item->unit_price;
            $newPrice = (float) $currentPrice->price;

            /*
             * IMPORTANT:
             *
             * If the cart price is currently zero, that means the
             * item was previously marked unavailable.
             *
             * Restoring 0 -> current price is NOT a real price change.
             */
            if ($oldPrice == 0) {
                $item->update([
                    'unit_price' => $currentPrice->price,
                ]);

                continue;
            }

            /*
             * A non-zero -> non-zero difference is a real price change.
             */
            if ($oldPrice !== $newPrice) {
                $item->update([
                    'unit_price' => $currentPrice->price,
                ]);

                $priceChanges[] = [
                    'type' => 'price_changed',
                    'item_id' => $item->id,
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                ];
            }
        }

        $cart->refresh();
        $cart->load('items.product');

        $changes = array_merge(
            $priceChanges,
            $availabilityChanges
        );

        $subtotal = $this->cartService->calculateSubtotal($cart);

        return [
            'cart' => $cart,
            'changes' => $changes,
            'price_changes' => $priceChanges,
            'availability_changes' => $availabilityChanges,
            'requires_price_confirmation' => count($changes) > 0,
            'subtotal' => $subtotal,
            'can_proceed_to_payment' => $subtotal > 0,
            'cart_has_payable_items' => $subtotal > 0,
        ];
    }

    /**
     * Confirm the current cart changes.
     *
     * Unavailable products are removed only after confirmation.
     */
    public function confirmPriceChanges(Cart $cart): array
    {
        return DB::transaction(function () use ($cart) {
            $cart->load('items.product');

            $outOfStockItems = [];

            foreach ($cart->items as $item) {
                if (! $this->inventoryService->isAvailable(
                    $item->product
                )) {
                    $outOfStockItems[] = $item;
                }
            }

            /*
             * Check prices only for currently available products.
             */
            $priceChanges = [];

            foreach ($cart->items as $item) {
                if (! $this->inventoryService->isAvailable(
                    $item->product
                )) {
                    continue;
                }

                $currentPrice = $this->cartService->getCurrentPrice(
                    $item->product
                );

                if (! $currentPrice) {
                    continue;
                }

                $oldPrice = (float) $item->unit_price;
                $newPrice = (float) $currentPrice->price;

                /*
                 * A zero cart price means the item was previously
                 * unavailable. Restoring its real price is not a
                 * customer-facing price change that requires approval.
                 */
                if ($oldPrice == 0) {
                    $item->update([
                        'unit_price' => $currentPrice->price,
                    ]);

                    continue;
                }

                if ($oldPrice !== $newPrice) {
                    $priceChanges[] = [
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'type' => 'price_changed',
                    ];
                }
            }

            /*
             * A real price change appeared between prepare()
             * and confirmation.
             */
            if (count($priceChanges) > 0) {
                throw new RuntimeException(
                    'قیمت سبد خرید دوباره تغییر کرده است. لطفاً قیمت‌های جدید را بررسی و تأیید کنید.'
                );
            }

            /*
             * Remove unavailable products after confirmation.
             */
            foreach ($outOfStockItems as $item) {
                $item->delete();
            }

            $cart->refresh();
            $cart->load('items.product');

            $subtotal = $this->cartService->calculateSubtotal($cart);

            /*
             * If nothing payable remains, do not allow payment.
             */
            if ($subtotal <= 0) {
                return [
                    'cart' => $cart,
                    'confirmed' => true,
                    'payment_allowed' => false,
                    'subtotal' => 0,
                    'message' => 'هیچ کالای قابل خریدی در سبد شما باقی نمانده است.',
                ];
            }

            return [
                'cart' => $cart,
                'confirmed' => true,
                'payment_allowed' => true,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Reject checkout changes.
     *
     * Nothing is deleted.
     */
    public function rejectPriceChanges(Cart $cart): array
    {
        $cart->refresh();
        $cart->load('items.product');

        return [
            'cart' => $cart,
            'confirmed' => false,
            'payment_allowed' => false,
            'subtotal' => $this->cartService->calculateSubtotal($cart),
        ];
    }
}
