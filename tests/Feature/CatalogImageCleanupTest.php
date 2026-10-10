<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_only_removes_images_from_the_109_imported_products(): void
    {
        $imported = Product::create([
            'name' => 'Imported', 'slug' => 'imported', 'sku' => 'TEST-IMPORT-001',
            'main_image' => 'https://example.com/imported.jpg',
        ]);
        $unrelated = Product::create([
            'name' => 'Other', 'slug' => 'other', 'sku' => 'TEST-IMPORT-110',
            'main_image' => 'https://example.com/other.jpg',
        ]);
        $imported->images()->create(['image_path' => 'products/imported.jpg']);
        $unrelated->images()->create(['image_path' => 'products/other.jpg']);

        $migration = require database_path('migrations/2026_10_10_120000_clear_imported_catalog_images.php');
        $migration->up();
        $migration->up();

        $this->assertNull($imported->fresh()->main_image);
        $this->assertSame(0, $imported->images()->count());
        $this->assertSame('https://example.com/other.jpg', $unrelated->fresh()->main_image);
        $this->assertSame(1, $unrelated->images()->count());
    }
}
