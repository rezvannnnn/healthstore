<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminInventoryService
{
    public function adjust(
        Inventory $inventory,
        int $quantityDelta,
        ?string $note,
        ?int $userId = null,
    ): Inventory {
        if ($quantityDelta === 0) {
            throw new RuntimeException('مقدار تغییر موجودی نمی‌تواند صفر باشد.');
        }

        return DB::transaction(function () use ($inventory, $quantityDelta, $note, $userId): Inventory {
            $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $before = (int) $inventory->quantity;
            $after = $before + $quantityDelta;

            if ($after < 0) {
                throw new RuntimeException('موجودی فیزیکی نمی‌تواند منفی شود.');
            }

            if ($after < (int) InventoryReservation::query()
                ->where('inventory_id', $inventory->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->sum('quantity')) {
                throw new RuntimeException('موجودی فیزیکی نمی‌تواند کمتر از مقدار رزروشده فعال باشد.');
            }

            $inventory->update(['quantity' => $after]);

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'user_id' => $userId,
                'type' => $quantityDelta > 0 ? 'increase' : 'decrease',
                'quantity_delta' => $quantityDelta,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => $note,
            ]);

            return $inventory->fresh(['product', 'warehouse']);
        });
    }
}
