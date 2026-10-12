<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
        if (array_key_exists('catalog_physical', $product->getAttributes())) {
            return (int) $product->getAttribute('catalog_physical');
        }

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
        if (array_key_exists('catalog_reserved', $product->getAttributes())) {
            return (int) $product->getAttribute('catalog_reserved');
        }

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
        return $product->is_active && $this->getAvailableQuantity($product) > 0;
    }

    /** @return array{earliest: ?string, latest: ?string, incomplete: bool}|null */
    public function getExpiryRange(Product $product): ?array
    {
        if (! $this->isAvailable($product)) {
            return null;
        }

        $reserved = fn (QueryBuilder $stock) => $stock->from('inventory_reservations')->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('inventory_id', 'inventories.id')->where('status', 'active')->where('expires_at', '>', now());
        $stock = Inventory::query()->where('product_id', $product->id)->where('is_active', true)->where('quantity', '>', 0)
            ->where('quantity', '>', $reserved)
            ->where(fn ($query) => $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))
            ->get(['expiry_date']);
        $dates = $stock->pluck('expiry_date')->filter()->sort()->values();

        return [
            'earliest' => $dates->first()?->toDateString(),
            'latest' => $dates->last()?->toDateString(),
            'incomplete' => $stock->contains(fn (Inventory $inventory) => $inventory->expiry_date === null),
        ];
    }
}
