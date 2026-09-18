<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_category_exposes_products_seo_and_structured_data(): void
    {
        $category = Category::query()->create([
            'name' => 'مراقبت پوست',
            'slug' => 'skin-care',
            'description' => 'محصولات مراقبت از پوست.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'name' => 'کرم مرطوب کننده',
            'slug' => 'skin-cream',
            'sku' => 'SKIN-001',
            'category_id' => $category->id,
            'short_description' => 'مناسب استفاده روزانه.',
            'is_active' => true,
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 125000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/categories/'.$category->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Category/Show')
            ->where('seo.title', 'مراقبت پوست | فروشگاه سلامت')
            ->where('seo.canonical', url('/categories/'.$category->slug))
            ->where('category.name', $category->name)
            ->where('pagination.total', 1)
            ->where('products.0.slug', $product->slug)
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.name', $category->name)
            ->where('structuredData.url', url('/categories/'.$category->slug))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.name', $product->name)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/products/'.$product->slug))
        );
    }

    public function test_inactive_category_is_not_publicly_available(): void
    {
        $category = Category::query()->create([
            'name' => 'دسته غیرفعال',
            'slug' => 'inactive-category',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $this->get('/categories/'.$category->slug)->assertNotFound();
    }

    public function test_sitemap_includes_active_category_url_but_not_inactive_category_url(): void
    {
        $active = Category::query()->create([
            'name' => 'مراقبت مو',
            'slug' => 'hair-care',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactive = Category::query()->create([
            'name' => 'دسته مخفی',
            'slug' => 'hidden-category',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(route('categories.show', $active->slug), false)
            ->assertDontSee(route('categories.show', $inactive->slug), false);
    }
}
