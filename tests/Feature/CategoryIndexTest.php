<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_category_index_lists_active_categories_with_product_counts_and_structure(): void
    {
        $category = Category::query()->create([
            'name' => 'مراقبت پوست',
            'slug' => 'skin-care',
            'description' => 'دسته محصولات مراقبت پوست.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::query()->create([
            'name' => 'محصول پوست',
            'slug' => 'skin-product',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Category/Index')
            ->where('seo.title', 'دسته‌بندی‌ها | فروشگاه سلامت')
            ->where('seo.canonical', url('/categories'))
            ->where('pagination.total', 1)
            ->where('categories.0.name', $category->name)
            ->where('categories.0.products_count', 1)
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.url', url('/categories'))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/categories/'.$category->slug))
        );
    }

    public function test_public_category_index_excludes_inactive_categories(): void
    {
        Category::query()->create([
            'name' => 'دسته فعال',
            'slug' => 'active-category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'name' => 'دسته غیرفعال',
            'slug' => 'inactive-category',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('pagination.total', 1)
            ->where('categories.0.slug', 'active-category')
            ->missing('categories.1')
        );
    }

    public function test_sitemap_contains_public_category_index(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(route('categories.index'), false);
    }
}
