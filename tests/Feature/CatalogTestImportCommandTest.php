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

        $productPrice = ProductPrice::query()
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame(
            'مکمل مولتی‌ویتامین و مینرال مخصوص بانوان با مجموعه‌ای از ویتامین‌ها، مواد معدنی و ترکیبات تغذیه‌ای برای پوشش نیازهای روزمره.',
            $product->description,
        );
        $this->assertSame(10230000.0, (float) $productPrice->price);
        $this->assertSame(12276000.0, (float) $productPrice->compare_at_price);

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
                ->where('product.description', $product->description)
                ->where('product.price', 10230000.0)
                ->where('product.compare_at_price', 12276000.0)
                ->where('structuredData.offers.priceCurrency', 'IRR')
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

    public function test_storefront_price_labels_are_in_rials(): void
    {
        foreach ([
            'resources/js/pages/Product/Show.vue',
            'resources/js/pages/Product/Index.vue',
            'resources/js/pages/Brand/Show.vue',
            'resources/js/pages/Category/Show.vue',
            'resources/js/pages/Cart/Index.vue',
            'resources/js/pages/Checkout.vue',
            'resources/js/pages/Order/Show.vue',
        ] as $path) {
            $content = file_get_contents(base_path($path));

            $this->assertStringNotContainsString('تومان', $content, $path);
            $this->assertStringContainsString('ریال', $content, $path);
        }
    }

}