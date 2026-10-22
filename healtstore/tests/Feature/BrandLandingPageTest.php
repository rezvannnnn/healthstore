<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_brand_exposes_products_seo_and_structured_data(): void
    {
        $brand = Brand::query()->create([
            'name' => 'برند سلامت',
            'slug' => 'salamat-brand',
            'description' => 'محصولات برند سلامت.',
            'logo' => '/images/salamat-brand.png',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'محصول برند سلامت',
            'slug' => 'salamat-product',
            'sku' => 'BRAND-001',
            'brand_id' => $brand->id,
            'short_description' => 'محصول آزمایشی برند.',
            'is_active' => true,
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 175000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/brands/'.$brand->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Brand/Show')
            ->where('seo.title', 'برند سلامت | فروشگاه سلامت')
            ->where('seo.canonical', url('/brands/'.$brand->slug))
            ->where('brand.name', $brand->name)
            ->where('brand.logo', url('/images/salamat-brand.png'))
            ->where('pagination.total', 1)
            ->where('products.0.slug', $product->slug)
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.name', $brand->name)
            ->where('structuredData.url', url('/brands/'.$brand->slug))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.name', $product->name)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/products/'.$product->slug))
        );
    }

    public function test_inactive_brand_is_not_publicly_available(): void
    {
        $brand = Brand::query()->create([
            'name' => 'برند غیرفعال',
            'slug' => 'inactive-brand',
            'is_active' => false,
        ]);

        $this->get('/brands/'.$brand->slug)->assertNotFound();
    }

    public function test_sitemap_includes_active_brand_url_but_not_inactive_brand_url(): void
    {
        $active = Brand::query()->create([
            'name' => 'برند فعال',
            'slug' => 'active-brand',
            'is_active' => true,
        ]);

        $inactive = Brand::query()->create([
            'name' => 'برند مخفی',
            'slug' => 'hidden-brand',
            'is_active' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(route('brands.show', $active->slug), false)
            ->assertDontSee(route('brands.show', $inactive->slug), false);
    }
}
