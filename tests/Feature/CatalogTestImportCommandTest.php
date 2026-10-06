<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTestImportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        @unlink(storage_path('app/testing/catalog-test-import.json'));

        parent::tearDown();
    }

    public function test_reversible_catalog_fixture_populates_products_brands_and_categories(): void
    {
        $this->artisan('catalog:test-import')
            ->assertExitCode(0);

        $this->assertSame(109, Product::query()->where('sku', 'like', 'TEST-IMPORT-%')->count());
        $this->assertSame(109, ProductPrice::query()->whereHas('product', fn ($query) => $query->where('sku', 'like', 'TEST-IMPORT-%'))->count());
        $this->assertGreaterThan(0, Brand::query()->count());
        $this->assertGreaterThan(0, Category::query()->count());

        $product = Product::query()
            ->where('sku', 'TEST-IMPORT-001')
            ->with(['brand', 'category'])
            ->firstOrFail();

        $this->get('/products')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Product/Index')
                ->has('products', 12)
                ->has('brands')
                ->has('categories')
            );

        $this->get('/brands')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Brand/Index')
                ->has('brands')
            );

        $this->get('/categories')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Category/Index')
                ->has('categories')
            );

        $this->get("/products/{$product->slug}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Product/Show')
                ->where('product.id', $product->id)
                ->where('product.brand', $product->brand->name)
                ->where('product.category', $product->category->name)
            );

        $this->get("/brands/{$product->brand->slug}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Brand/Show')
                ->has('products')
            );

        $this->get("/categories/{$product->category->slug}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Category/Show')
                ->has('products')
            );

        $this->artisan('catalog:test-rollback')
            ->assertExitCode(0);

        $this->assertSame(0, Product::query()->where('sku', 'like', 'TEST-IMPORT-%')->count());
        $this->assertSame(0, ProductPrice::query()->count());
    }
}
