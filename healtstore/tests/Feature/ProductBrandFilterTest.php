<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBrandFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_catalog_filters_by_active_brand(): void
    {
        $brandA = Brand::query()->create([
            'name' => 'برند الف',
            'slug' => 'brand-a',
            'is_active' => true,
        ]);
        $brandB = Brand::query()->create([
            'name' => 'برند ب',
            'slug' => 'brand-b',
            'is_active' => true,
        ]);

        $productA = Product::query()->create([
            'name' => 'محصول الف',
            'slug' => 'product-a',
            'brand_id' => $brandA->id,
            'is_active' => true,
        ]);
        $productB = Product::query()->create([
            'name' => 'محصول ب',
            'slug' => 'product-b',
            'brand_id' => $brandB->id,
            'is_active' => true,
        ]);

        ProductPrice::query()->insert([
            [
                'product_id' => $productA->id,
                'price_type' => 'retail',
                'price' => 100000,
                'min_quantity' => 1,
                'is_active' => true,
            ],
            [
                'product_id' => $productB->id,
                'price_type' => 'retail',
                'price' => 200000,
                'min_quantity' => 1,
                'is_active' => true,
            ],
        ]);

        $response = $this->get('/products?brand='.$brandA->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Index')
            ->where('filters.brand', $brandA->slug)
            ->where('products.0.slug', $productA->slug)
            ->missing('products.1')
            ->where('brands.0.slug', $brandA->slug)
        );
    }

    public function test_product_catalog_does_not_use_inactive_brand_filter(): void
    {
        $inactiveBrand = Brand::query()->create([
            'name' => 'برند غیرفعال',
            'slug' => 'inactive-brand',
            'is_active' => false,
        ]);

        Product::query()->create([
            'name' => 'محصول مخفی برند',
            'slug' => 'hidden-brand-product',
            'brand_id' => $inactiveBrand->id,
            'is_active' => true,
        ]);

        $response = $this->get('/products?brand='.$inactiveBrand->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.brand', $inactiveBrand->slug)
            ->where('products', fn ($products) => count($products) === 0)
            ->where('brands', fn ($brands) => ! collect($brands)->contains(
                fn ($brand) => $brand['slug'] === $inactiveBrand->slug,
            ))
        );
    }
}
