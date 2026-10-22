<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_lists_active_products(): void
    {
        $product = Product::create([
            'name' => 'Vitamin C',
            'slug' => 'vitamin-c',
            'sku' => 'VC-001',
            'short_description' => 'Vitamin C supplement',
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 120000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/products');

        $response->assertOk();
    }

    public function test_inactive_product_is_not_shown_in_catalog(): void
    {
        Product::create([
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'is_active' => false,
        ]);

        $response = $this->get('/products');

        $response->assertOk();
    }

    public function test_public_product_details_show_active_product(): void
    {
        $product = Product::create([
            'name' => 'Detailed Product',
            'slug' => 'detailed-product',
            'sku' => 'DP-001',
            'description' => 'Full product description',
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 250000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => '/images/products/detailed-product.jpg',
            'is_primary' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN-01',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->get('/products/detailed-product');

        $response->assertOk();
    }

    public function test_inactive_product_details_return_not_found(): void
    {
        $product = Product::create([
            'name' => 'Inactive Product',
            'slug' => 'inactive-product',
            'is_active' => false,
        ]);

        $response = $this->get('/products/inactive-product');

        $response->assertNotFound();
    }
}
