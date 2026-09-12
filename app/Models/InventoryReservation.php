<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'inventory_id', 'quantity', 'status',
        'expires_at', 'released_at', 'consumed_at',
    ];

    protected $casts = [
        'quantity' => 'integer', 'expires_at' => 'datetime',
        'released_at' => 'datetime', 'consumed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Inventory, $this> */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
