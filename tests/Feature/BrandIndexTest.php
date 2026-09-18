<?php

namespace Tests\Feature;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_brand_index_lists_active_brands_with_product_counts(): void
    {
        $brand = Brand::query()->create([
            'name' => 'برند سلامت',
            'slug' => 'salamat-brand',
            'description' => 'توضیح برند.',
            'is_active' => true,
        ]);

        $response = $this->get('/brands');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Brand/Index')
            ->where('seo.title', 'برندها | فروشگاه سلامت')
            ->where('seo.canonical', url('/brands'))
            ->where('pagination.total', 1)
            ->where('brands.0.name', $brand->name)
            ->where('brands.0.products_count', 0)
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.url', url('/brands'))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/brands/'.$brand->slug))
        );
    }

    public function test_public_brand_index_excludes_inactive_brands(): void
    {
        Brand::query()->create([
            'name' => 'برند فعال',
            'slug' => 'active-brand',
            'is_active' => true,
        ]);

        Brand::query()->create([
            'name' => 'برند غیرفعال',
            'slug' => 'inactive-brand',
            'is_active' => false,
        ]);

        $response = $this->get('/brands');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('pagination.total', 1)
            ->where('brands.0.slug', 'active-brand')
            ->missing('brands.1')
        );
    }

    public function test_sitemap_contains_public_brand_index(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(route('brands.index'), false);
    }
}
