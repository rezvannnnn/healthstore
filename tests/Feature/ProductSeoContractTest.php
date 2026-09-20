<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeoContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_exposes_seo_metadata(): void
    {
        $response = $this->get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Index')
            ->where('seo.title', 'محصولات | فروشگاه سلامت')
            ->where('seo.canonical', url('/products'))
        );
    }

    public function test_product_show_uses_name_and_short_description_when_seo_fields_are_empty(): void
    {
        $product = $this->createProduct([
            'name' => 'کرم مرطوب کننده',
            'slug' => 'moisturizing-cream',
            'short_description' => 'کرم مناسب پوست خشک.',
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Show')
            ->where('seo.title', $product->name)
            ->where('seo.description', $product->short_description)
            ->where('seo.canonical', url('/products/'.$product->slug))
        );
    }

    public function test_product_show_exposes_product_structured_data(): void
    {
        $product = $this->createProduct([
            'name' => 'کرم مرطوب کننده',
            'slug' => 'structured-data-cream',
            'sku' => 'SKU-123',
            'short_description' => 'کرم مناسب پوست خشک.',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 125000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Structured Data Warehouse',
            'code' => 'STRUCTURED-DATA-WH',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('structuredData.@context', 'https://schema.org')
            ->where('structuredData.@type', 'Product')
            ->where('structuredData.name', $product->name)
            ->where('structuredData.description', $product->short_description)
            ->where('structuredData.url', url('/products/'.$product->slug))
            ->where('structuredData.sku', $product->sku)
            ->where('structuredData.offers.@type', 'Offer')
            ->where('structuredData.offers.price', 125000)
            ->where('structuredData.offers.priceCurrency', 'IRR')
            ->where('structuredData.offers.availability', 'https://schema.org/InStock')
        );
    }

    public function test_product_show_uses_custom_seo_title(): void
    {
        $product = $this->createProduct([
            'name' => 'شامپو ضد شوره',
            'slug' => 'anti-dandruff-shampoo',
            'seo_title' => 'بهترین شامپو ضد شوره | فروشگاه سلامت',
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('seo.title', $product->seo_title)
        );
    }

    public function test_product_show_uses_custom_seo_description(): void
    {
        $product = $this->createProduct([
            'name' => 'ضد آفتاب',
            'slug' => 'sunscreen',
            'short_description' => 'توضیح عمومی.',
            'seo_description' => 'توضیح اختصاصی برای موتورهای جستجو.',
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('seo.description', $product->seo_description)
        );
    }

    public function test_product_show_uses_custom_canonical_url(): void
    {
        $product = $this->createProduct([
            'name' => 'ویتامین دی',
            'slug' => 'vitamin-d',
            'canonical_url' => 'https://example.com/health/vitamin-d',
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('seo.canonical', $product->canonical_url)
        );
    }

    public function test_inactive_product_does_not_expose_seo_page(): void
    {
        $product = $this->createProduct([
            'name' => 'محصول غیر فعال',
            'slug' => 'inactive-seo-product',
            'is_active' => false,
        ]);

        $this->get('/products/'.$product->slug)->assertNotFound();
    }

    public function test_product_show_keeps_related_products_limited(): void
    {
        $product = $this->createProduct([
            'name' => 'محصول اصلی',
            'slug' => 'main-seo-product',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 100000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        for ($index = 1; $index <= 6; $index++) {
            $this->createProduct([
                'name' => 'محصول مرتبط '.$index,
                'slug' => 'related-seo-product-'.$index,
                'category_id' => $product->category_id,
            ]);
        }

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('relatedProducts', fn ($related) => count($related) <= 4)
        );
    }

    /** @param array<string, mixed> $overrides */
    private function createProduct(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'name' => 'محصول آزمایشی',
            'slug' => 'test-product-'.uniqid(),
            'sku' => null,
            'short_description' => 'توضیح محصول.',
            'is_active' => true,
        ], $overrides));
    }
}
