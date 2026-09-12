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
     * The current product price is stored in unit_price.
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

            $price = $this->getCurrentPrice($product);

            if (! $price) {
                throw new RuntimeException(
                    'برای این محصول قیمت فعالی ثبت نشده است.'
                );
            }

            $cart = $this->getCartForUser($userId);

            $item = $cart->items()
                ->where('product_id', $productId)
                ->first();

            if ($item) {
                $item->quantity += $quantity;
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
     * Get the current active price of a product.
     */
    public function getCurrentPrice(Product $product): ?ProductPrice
    {
        $now = now();

        return $product->prices()
            ->where('is_active', true)
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
            $currentPrice = $this->getCurrentPrice($item->product);

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
                $currentPrice = $this->getCurrentPrice($item->product);

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
     *
     * This method intentionally does not reload the items relation,
     * because Checkout needs the product relation to remain loaded
     * for the Inertia/Vue response.
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