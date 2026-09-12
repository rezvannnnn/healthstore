<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'user_id', 'address_id', 'customer_type', 'business_profile_id',
        'status', 'payment_status', 'subtotal', 'discount_amount', 'shipping_amount',
        'total_amount', 'currency', 'recipient_name', 'recipient_phone', 'province',
        'city', 'shipping_address', 'postal_code', 'customer_note', 'admin_note',
        'confirmed_at', 'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2', 'confirmed_at' => 'datetime', 'paid_at' => 'datetime',
        'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<InventoryReservation, $this> */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }
}
