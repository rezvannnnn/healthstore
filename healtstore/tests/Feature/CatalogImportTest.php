<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private string $catalogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalogFile = tempnam(sys_get_temp_dir(), 'catalog-');
        file_put_contents($this->catalogFile, json_encode([
            'version' => 1,
            'category_paths' => [['مکمل دارویی', 'ویتامین ها']],
            'products' => [[
                'name' => 'محصول آزمایشی 30 عددی',
                'description' => 'توضیح اختصاصی محصول آزمایشی.',
                'description_mode' => 'original_from_structured_facts',
                'category_path' => ['مکمل دارویی', 'ویتامین ها'],
                'main_image' => null,
                'price' => 999999,
                'expiry_date' => '2030-01-01',
                'sku' => 'COMPETITOR-123',
                'barcode' => '123456789',
                'sources' => [
                    ['key' => 'begoosib-123', 'url' => 'https://begoosib.com/product/test/'],
                    ['key' => 'davaxana-456', 'url' => 'https://davaxana.com/product/456/test'],
                ],
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        @unlink($this->catalogFile);
        parent::tearDown();
    }

    public function test_import_uses_local_defaults_and_preserves_existing_catalog(): void
    {
        $existing = Product::create(['name' => 'Existing', 'slug' => 'existing', 'sku' => 'OLD-1']);
        $this->artisan('catalog:import', ['file' => $this->catalogFile])->assertSuccessful();
        $product = Product::where('sku', 'DKH-000001')->firstOrFail();
        $this->assertNull($product->expiry_date);
        $this->assertNull($product->barcode);
        $this->assertNull($product->canonical_url);
        $this->assertSame(0, $product->prices()->count());
        $this->assertSame(10, $product->inventories()->sum('quantity'));
        $this->assertNull($product->inventories()->firstOrFail()->expiry_date);
        $this->assertSame('مکمل دارویی', $product->category->parent->name);
        $this->assertSame(2, DB::table('catalog_import_sources')->count());
        $this->assertSame('OLD-1', $existing->fresh()->sku);
        $this->get('/products/'.$product->slug)->assertSuccessful();
    }

    public function test_reimport_does_not_reset_admin_edits_or_restock_products(): void
    {
        $this->artisan('catalog:import', ['file' => $this->catalogFile])->assertSuccessful();
        $product = Product::where('sku', 'DKH-000001')->firstOrFail();
        $product->update(['name' => 'Edited product', 'expiry_date' => '2030-01-01']);
        Inventory::where('product_id', $product->id)->update(['quantity' => 3]);
        $this->artisan('catalog:import', ['file' => $this->catalogFile])->assertSuccessful();
        $this->assertSame(1, Product::count());
        $this->assertSame('Edited product', $product->fresh()->name);
        $this->assertSame(3, $product->inventories()->sum('quantity'));
        $this->assertSame('2030-01-01', $product->fresh()->expiry_date->toDateString());
    }

    public function test_dry_run_leaves_database_unchanged(): void
    {
        $this->artisan('catalog:import', ['file' => $this->catalogFile, '--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Product::count());
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('warehouses', 0);
    }
}
