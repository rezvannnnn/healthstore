<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Support\Carbon;

class InventoryService
{
    /**
     * Get the total physical inventory of a product.
     *
     * This is the quantity physically present in active,
     * non-expired inventory records.
     */
    public function getPhysicalQuantity(Product $product): int
    {
        return (int) Inventory::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhereDate(
                        'expiry_date',
                        '>=',
                        Carbon::today()
                    );
            })
            ->sum('quantity');
    }

    /**
     * Get the quantity currently reserved by active reservations.
     *
     * Only reservations that:
     * - are active
     * - have not expired
     *
     * are counted.
     */
    public function getReservedQuantity(Product $product): int
    {
        return (int) InventoryReservation::query()
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /**
     * Get the quantity currently available for sale.
     *
     * Available = Physical inventory - Active reservations
     */
    public function getAvailableQuantity(Product $product): int
    {
        $physicalQuantity = $this->getPhysicalQuantity($product);

        $reservedQuantity = $this->getReservedQuantity($product);

        return max(
            0,
            $physicalQuantity - $reservedQuantity
        );
    }

    /**
     * Determine whether a product is currently available for sale.
     */
    public function isAvailable(Product $product): bool
    {
        return $this->getAvailableQuantity($product) > 0;
    }
}