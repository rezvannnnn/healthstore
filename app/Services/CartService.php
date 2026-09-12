<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService
{
    /**
     * Get or create the active cart for the user.
     */
    public function getCartForUser(int $userId): Cart
    {
        return Cart::firstOrCreate(
            [
                'user_id' => $userId,
                'status' => 'active',
            ],
            [
                'session_id' => null,
            ]
        );
    }

    /**
     * Add a product to the cart.
     *
     * The effective price for the resulting quantity is stored in unit_price.
     */
    public function addItem(
        int $userId,
        int $productId,
        int $quantity = 1
    ): CartItem {
        if ($quantity < 1) {
            throw new RuntimeException(
                'تعداد محصول باید حداقل ۱ باشد.'
            );
        }

        return DB::transaction(function () use (
            $userId,
            $productId,
            $quantity
        ) {
            $product = Product::query()
                ->where('id', $productId)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                throw new RuntimeException(
                    'محصول مورد نظر موجود نیست یا غیرفعال شده است.'
                );
            }

            $cart = $this->getCartForUser($userId);
            $item = $cart->items()
                ->where('product_id', $productId)
                ->first();
            $newQuantity = ($item === null ? 0 : $item->quantity) + $quantity;

            $price = $this->getCurrentPrice($product, $newQuantity);

            if (! $price) {
                throw new RuntimeException(
                    'برای این محصول و تعداد انتخاب‌شده قیمت فعالی ثبت نشده است.'
                );
            }

            if ($item) {
                $item->quantity = $newQuantity;
                $item->unit_price = $price->price;
                $item->save();

                return $item;
            }

            return $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $price->price,
            ]);
        });
    }

    /**
     * Get the current active price for the requested quantity.
     */
    public function getCurrentPrice(
        Product $product,
        int $quantity = 1
    ): ?ProductPrice {
        $now = now();
        $quantity = max(1, $quantity);

        return $product->prices()
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('min_quantity')
            ->first();
    }

    /**
     * Check cart prices and immediately update changed prices.
     *
     * Returns the items whose prices have changed.
     */
    public function checkPriceChanges(Cart $cart): array
    {
        $cart->load('items.product.prices');

        $changes = [];

        foreach ($cart->items as $item) {
            $currentPrice = $this->getCurrentPrice($item->product, $item->quantity);

            if (! $currentPrice) {
                $changes[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'old_price' => (float) $item->unit_price,
                    'new_price' => null,
                    'type' => 'unavailable',
                ];

                continue;
            }

            $oldPrice = (float) $item->unit_price;
            $newPrice = (float) $currentPrice->price;

            if ($oldPrice !== $newPrice) {
                $item->update([
                    'unit_price' => $currentPrice->price,
                ]);

                $changes[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'type' => 'price_changed',
                ];
            }
        }

        return $changes;
    }

    /**
     * Apply the current database prices to the cart.
     */
    public function updateCartPrices(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            $cart->load('items.product');

            foreach ($cart->items as $item) {
                $currentPrice = $this->getCurrentPrice($item->product, $item->quantity);

                if ($currentPrice) {
                    $item->update([
                        'unit_price' => $currentPrice->price,
                    ]);
                }
            }
        });
    }

    /**
     * Calculate the cart subtotal using the currently loaded items.
     */
    public function calculateSubtotal(Cart $cart): float
    {
        if (! $cart->relationLoaded('items')) {
            $cart->load('items');
        }

        return $cart->items->sum(function (CartItem $item) {
            return (float) $item->unit_price * $item->quantity;
        });
    }
}
