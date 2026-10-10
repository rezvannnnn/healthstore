<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'product_type',
        'unit',
        'quantity_per_unit',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
        'canonical_url',
        'specifications',
        'expiry_date',
        'main_image',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'specifications' => 'array',
    ];

    public function scopeForCatalog(Builder $query): void
    {
        $query->with('prices')->addSelect([
            'catalog_physical' => Inventory::query()->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('product_id', 'products.id')->where('is_active', true)->where('quantity', '>', 0)
                ->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString())),
            'catalog_reserved' => InventoryReservation::query()->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('product_id', 'products.id')->where('status', 'active')->where('expires_at', '>', now()),
        ]);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /** @return HasMany<Inventory, $this> */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
