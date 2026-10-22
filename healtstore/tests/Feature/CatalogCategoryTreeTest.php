<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_categories_include_products_from_nested_children(): void
    {
        $root = Category::create(['name' => 'مکمل دارویی', 'slug' => 'supplements', 'is_active' => true]);
        $child = Category::create(['name' => 'ویتامین', 'slug' => 'vitamins', 'parent_id' => $root->id, 'is_active' => true]);
        $leaf = Category::create(['name' => 'ویتامین ب', 'slug' => 'vitamin-b', 'parent_id' => $child->id, 'is_active' => true]);
        Product::create(['name' => 'ویتامین نمونه', 'slug' => 'sample-vitamin', 'category_id' => $leaf->id, 'is_active' => true]);
        $this->assertEqualsCanonicalizing([$root->id, $child->id, $leaf->id], CatalogCategories::descendantIds($root->id));
        $this->assertSame(1, CatalogCategories::activeProductCounts()[$root->id]);
        $this->get('/categories/supplements')->assertSuccessful()->assertInertia(fn ($page) => $page->where('pagination.total', 1));
        $this->get('/products?category=supplements')->assertSuccessful()->assertInertia(fn ($page) => $page->where('pagination.total', 1));
    }
}
