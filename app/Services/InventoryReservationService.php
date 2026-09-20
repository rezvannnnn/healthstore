<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryReservationService
{
    /**
     * Reserve a quantity of a product for an order.
     *
     * Physical inventory is NOT reduced at reservation time.
     * The reservation itself makes the quantity unavailable for
     * other orders.
     */
    public function reserve(
        Order $order,
        Product $product,
        int $quantity
    ): Collection {
        if ($quantity < 1) {
            throw new RuntimeException(
                'تعداد رزرو باید حداقل ۱ باشد.'
            );
        }

        return DB::transaction(function () use (
            $order,
            $product,
            $quantity
        ) {
            /*
             * Lock all relevant inventory rows first.
             *
             * This prevents concurrent orders from calculating
             * availability from stale inventory information.
             */
            $inventoryRows = Inventory::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhereDate(
                            'expiry_date',
                            '>=',
                            now()->toDateString()
                        );
                })
                ->orderBy('expiry_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /*
             * Calculate available quantity in every inventory row.
             *
             * Physical inventory remains unchanged.
             * Active reservations are subtracted virtually.
             */
            $totalAvailable = 0;

            $rowAvailability = [];

            foreach ($inventoryRows as $inventory) {
                $alreadyReserved = (int) InventoryReservation::query()
                    ->where('inventory_id', $inventory->id)
                    ->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->sum('quantity');

                $availableInRow = max(
                    0,
                    (int) $inventory->quantity - $alreadyReserved
                );

                if ($availableInRow <= 0) {
                    continue;
                }

                $rowAvailability[$inventory->id] = $availableInRow;

                $totalAvailable += $availableInRow;
            }

            /*
             * Never create partial reservations.
             */
            if ($totalAvailable < $quantity) {
                throw new RuntimeException(
                    "موجودی محصول «{$product->name}» برای رزرو کافی نیست."
                );
            }

            $remaining = $quantity;

            $reservations = new Collection;

            foreach ($inventoryRows as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $availableInRow =
                    $rowAvailability[$inventory->id] ?? 0;

                if ($availableInRow <= 0) {
                    continue;
                }

                $reserveQuantity = min(
                    $remaining,
                    $availableInRow
                );

                $reservation = InventoryReservation::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $reserveQuantity,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(20),
                    'released_at' => null,
                    'consumed_at' => null,
                ]);

                $reservations->push($reservation);

                $remaining -= $reserveQuantity;
            }

            /*
             * Defensive check.
             */
            if ($remaining > 0) {
                throw new RuntimeException(
                    "رزرو کامل محصول «{$product->name}» انجام نشد."
                );
            }

            return $reservations;
        });
    }

    /**
     * Consume an active reservation after successful payment.
     *
     * Physical inventory is reduced here.
     */
    public function consume(
        InventoryReservation $reservation
    ): bool {
        return DB::transaction(function () use ($reservation) {
            $lockedReservation = InventoryReservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedReservation) {
                throw new RuntimeException(
                    'رزرو موجودی پیدا نشد.'
                );
            }

            if ($lockedReservation->status !== 'active') {
                return false;
            }

            /*
             * An expired reservation cannot be consumed.
             */
            if ($lockedReservation->expires_at->isPast()) {
                return false;
            }

            /*
             * Lock the physical inventory row before reducing stock.
             */
            $inventory = $lockedReservation->inventory()
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new RuntimeException(
                    'رکورد موجودی مربوط به این رزرو پیدا نشد.'
                );
            }

            if ($inventory->quantity < $lockedReservation->quantity) {
                throw new RuntimeException(
                    'موجودی فیزیکی برای مصرف رزرو کافی نیست.'
                );
            }

            /*
             * The reserved quantity now becomes a completed sale,
             * so remove it from physical inventory.
             */
            $inventory->decrement(
                'quantity',
                $lockedReservation->quantity
            );

            $lockedReservation->update([
                'status' => 'consumed',
                'consumed_at' => now(),
                'released_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Release an active reservation.
     *
     * Physical inventory is NOT changed here because the physical
     * quantity was never reduced when the reservation was created.
     */
    public function release(
        InventoryReservation $reservation
    ): bool {
        return DB::transaction(function () use ($reservation) {
            $lockedReservation = InventoryReservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedReservation) {
                throw new RuntimeException(
                    'رزرو موجودی پیدا نشد.'
                );
            }

            if ($lockedReservation->status !== 'active') {
                return false;
            }

            $lockedReservation->update([
                'status' => 'released',
                'released_at' => now(),
                'consumed_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Release all expired active reservations.
     *
     * Returns the number of successfully released reservations.
     */
    public function releaseExpired(): int
    {
        $releasedCount = 0;

        $reservations = InventoryReservation::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($reservations as $reservation) {
            if ($this->release($reservation)) {
                $releasedCount++;
            }
        }

        return $releasedCount;
    }
}
