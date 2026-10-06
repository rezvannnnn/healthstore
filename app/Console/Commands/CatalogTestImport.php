<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CatalogTestImport extends Command
{
    protected $signature = 'catalog:test-import';

    protected $description = 'Import the committed 109-row catalog fixture as isolated reversible test data';

    public function handle(): int
    {
        $manifestPath = $this->manifestPath();

        if (is_file($manifestPath)) {
            $this->error('یک Import آزمایشی فعال است. ابتدا catalog:test-rollback را اجرا کنید.');

            return self::FAILURE;
        }

        $files = glob(base_path('database/seeders/fixtures/catalog_test_import/*.json')) ?: [];
        sort($files);

        if ($files === []) {
            $this->error('فایل‌های fixture پیدا نشدند.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($files as $file) {
            $decoded = json_decode(
                (string) file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            if (! is_array($decoded)) {
                throw new RuntimeException("Invalid fixture: {$file}");
            }

            foreach ($decoded as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        usort($rows, fn (array $a, array $b): int => ((int) ($a['row'] ?? 0)) <=> ((int) ($b['row'] ?? 0)));

        if (count($rows) !== 109) {
            $this->error('تعداد ردیف‌های fixture باید دقیقاً 109 باشد؛ مقدار فعلی: '.count($rows));

            return self::FAILURE;
        }

        $manifest = [
            'source' => 'pharmacy_products_images_researched_draft(1).xlsx',
            'fixture_files' => array_map(fn (string $file): string => basename($file), $files),
            'imported_at' => now()->toISOString(),
            'products' => [],
            'brands' => [],
            'categories' => [],
            'warehouses' => [],
        ];

        try {
            DB::transaction(function () use ($rows, &$manifest): void {
                foreach ($rows as $row) {
                    $categoryId = $this->resolveCategory($row, $manifest);
                    $brandId = $this->resolveBrand($row, $manifest);

                    $rowNumber = (int) $row['row'];
                    $name = trim((string) ($row['name'] ?? ''));

                    if ($name === '') {
                        throw new RuntimeException("نام محصول در ردیف {$rowNumber} خالی است.");
                    }

                    $product = Product::create([
                        'brand_id' => $brandId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => $this->testSlug($name, $rowNumber),
                        'sku' => $this->testSku($rowNumber),
                        'barcode' => null,
                        'product_type' => null,
                        'unit' => null,
                        'quantity_per_unit' => $row['quantity_per_unit'] ?? null,
                        'short_description' => $row['short_description'] ?? null,
                        'description' => null,
                        'seo_title' => null,
                        'seo_description' => null,
                        'canonical_url' => null,
                        'main_image' => $row['image'] ?? null,
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => $rowNumber,
                    ]);

                    $manifest['products'][] = $product->id;

                    if (($row['price'] ?? null) !== null) {
                        ProductPrice::create([
                            'product_id' => $product->id,
                            'price_type' => 'retail',
                            'price' => $row['price'],
                            'compare_at_price' => null,
                            'min_quantity' => 1,
                            'is_active' => true,
                            'starts_at' => null,
                            'ends_at' => null,
                        ]);
                    }

                    $inventoryQuantity = $this->integerOrNull($row['inventory_quantity'] ?? null);

                    if ($inventoryQuantity !== null && $inventoryQuantity > 0) {
                        $warehouse = $this->resolveWarehouse(
                            trim((string) ($row['warehouse'] ?? 'مرکزی')),
                            $manifest,
                        );

                        Inventory::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouse->id,
                            'quantity' => $inventoryQuantity,
                            'minimum_quantity' => $this->integerOrDefault($row['minimum_quantity'] ?? null, 0),
                            'batch_number' => $row['batch_number'] ?? null,
                            'expiry_date' => $row['expiry_date'] ?? null,
                            'is_active' => true,
                        ]);
                    }
                }

                if (! is_dir(dirname($this->manifestPath()))) {
                    mkdir(dirname($this->manifestPath()), 0775, true);
                }

                file_put_contents(
                    $this->manifestPath(),
                    json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                );
            });
        } catch (Throwable $exception) {
            @unlink($this->manifestPath());

            throw $exception;
        }

        $this->info('Import آزمایشی با موفقیت انجام شد.');
        $this->line('محصولات: '.count($manifest['products']));
        $this->line('برندها (جدید): '.count($manifest['brands']));
        $this->line('دسته‌بندی‌ها (جدید): '.count($manifest['categories']));
        $this->line('انبارها (جدید): '.count($manifest['warehouses']));
        $this->line('SKUهای تست با پیشوند TEST-IMPORT- ساخته شدند تا حذف/بازگشت امن باشد.');

        return self::SUCCESS;
    }

    protected function resolveBrand(array $row, array &$manifest): int
    {
        $name = trim((string) ($row['brand'] ?? ''));

        if ($name === '') {
            return 0;
        }

        $brand = Brand::query()->where('name', $name)->first();

        if ($brand) {
            return $brand->id;
        }

        $brand = Brand::create([
            'name' => $name,
            'slug' => $this->uniqueSlug('brand', $name),
            'description' => null,
            'logo' => null,
            'is_active' => true,
        ]);

        $manifest['brands'][] = $brand->id;

        return $brand->id;
    }

    protected function resolveCategory(array $row, array &$manifest): int
    {
        $categoryName = trim((string) ($row['category'] ?? ''));
        $subCategoryName = trim((string) ($row['sub_category'] ?? ''));

        if ($categoryName === '') {
            return 0;
        }

        $parent = Category::query()->where('parent_id', null)->where('name', $categoryName)->first();

        if (! $parent) {
            $parent = Category::create([
                'parent_id' => null,
                'name' => $categoryName,
                'slug' => $this->uniqueSlug('category', $categoryName),
                'description' => null,
                'image' => null,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $manifest['categories'][] = $parent->id;
        }

        if ($subCategoryName === '') {
            return $parent->id;
        }

        $child = Category::query()
            ->where('parent_id', $parent->id)
            ->where('name', $subCategoryName)
            ->first();

        if ($child) {
            return $child->id;
        }

        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => $subCategoryName,
            'slug' => $this->uniqueSlug('category', $parent->id.'-'.$subCategoryName),
            'description' => null,
            'image' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $manifest['categories'][] = $child->id;

        return $child->id;
    }

    protected function resolveWarehouse(string $name, array &$manifest): Warehouse
    {
        $name = $name !== '' ? $name : 'مرکزی';

        $warehouse = Warehouse::query()->where('name', $name)->first();

        if ($warehouse) {
            return $warehouse;
        }

        $warehouse = Warehouse::create([
            'name' => $name,
            'code' => $this->uniqueWarehouseCode($name),
            'description' => null,
            'is_active' => true,
        ]);

        $manifest['warehouses'][] = $warehouse->id;

        return $warehouse;
    }

    protected function uniqueSlug(string $prefix, string $value): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = md5($value);
        }

        $base = $prefix.'-'.$base;
        $slug = $base;
        $counter = 2;

        while (Brand::query()->where('slug', $slug)->exists()
            || Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    protected function uniqueWarehouseCode(string $value): string
    {
        $base = 'TEST-'.strtoupper(substr(md5($value), 0, 8));
        $code = $base;
        $counter = 2;

        while (Warehouse::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$counter++;
        }

        return $code;
    }

    protected function testSku(int $rowNumber): string
    {
        return 'TEST-IMPORT-'.str_pad((string) $rowNumber, 3, '0', STR_PAD_LEFT);
    }

    protected function testSlug(string $name, int $rowNumber): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'product';
        }

        return 'test-import-'.$rowNumber.'-'.$slug;
    }

    protected function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function integerOrDefault(mixed $value, int $default): int
    {
        return $this->integerOrNull($value) ?? $default;
    }

    protected function manifestPath(): string
    {
        return storage_path('app/testing/catalog-test-import.json');
    }
}
