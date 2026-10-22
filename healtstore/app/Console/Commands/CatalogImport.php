<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class CatalogImport extends Command
{
    protected $signature = 'catalog:import {file : Prepared local catalog JSON} {--dry-run : Validate without writing} {--skip-image-inspection : Reuse a successful dry-run image check; still validate paths} {--sku-prefix=DKH : Local SKU prefix}';

    protected $description = 'Import compared public product facts and local images, with ten units and no expiry or prices';

    private array $categories = [];

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error('Catalog file does not exist.');

            return self::FAILURE;
        }
        $catalog = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        Validator::make($catalog, [
            'version' => ['required', 'integer', 'in:1'],
            'category_paths' => ['present', 'array'],
            'category_paths.*' => ['array', 'min:1', 'max:10'],
            'category_paths.*.*' => ['string', 'max:255'],
            'products' => ['required', 'array', 'min:1'],
        ])->validate();
        $rowRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description_mode' => ['required', 'in:original_from_structured_facts'],
            'category_path' => ['present', 'array', 'max:10'],
            'category_path.*' => ['required', 'string', 'max:255'],
            'main_image' => ['nullable', 'string', 'regex:~^products/catalog/[a-f0-9]{32}\.(jpg|jpeg|png|webp|gif)$~'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.key' => ['required', 'string', 'regex:/^(begoosib|davaxana)-[0-9]+$/'],
            'sources.*.url' => ['required', 'url', 'regex:~^https://(begoosib|davaxana)\.com/product/~'],
        ];
        $prefix = (string) $this->option('sku-prefix');
        if (! preg_match('/^[A-Z][A-Z0-9-]{1,15}$/', $prefix)) {
            throw new RuntimeException('Invalid local SKU prefix.');
        }
        $sourceKeys = [];
        $validatedRows = 0;
        foreach ($catalog['products'] as $row) {
            if (! is_array($row)) {
                throw new RuntimeException('Invalid product row.');
            }
            Validator::make($row, $rowRules)->validate();
            foreach ($row['sources'] as $source) {
                if (isset($sourceKeys[$source['key']])) {
                    throw new RuntimeException('A source product occurs in more than one group: '.$source['key']);
                }
                $sourceKeys[$source['key']] = true;
            }
            if (! $this->option('skip-image-inspection') && ! empty($row['main_image'])) {
                $image = storage_path('app/public/'.$row['main_image']);
                if (! is_file($image) || @getimagesize($image) === false) {
                    throw new RuntimeException('Missing or invalid local product image: '.$row['main_image']);
                }
            }
            $validatedRows++;
            if ($validatedRows % 1000 === 0) {
                $this->line('Validated '.$validatedRows.' products.');
            }
        }
        $this->info(count($catalog['products']).' unique products; '.count($sourceKeys).' source products validated.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $result = DB::transaction(function () use ($catalog, $prefix): array {
            $created = 0;
            $skipped = 0;
            $nextSku = 1;
            foreach (Product::query()->where('sku', 'like', $prefix.'-%')->pluck('sku') as $sku) {
                if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $sku, $match)) {
                    $nextSku = max($nextSku, (int) $match[1] + 1);
                }
            }
            foreach (Category::all() as $category) {
                $this->categories[($category->parent_id ?? 0).'|'.$this->normalize($category->name)] = $category->id;
            }
            foreach ($catalog['category_paths'] as $path) {
                $this->categoryId($path);
            }
            $warehouse = Warehouse::query()->where('is_active', true)->orderBy('id')->first()
                ?? Warehouse::create(['name' => 'انبار اصلی', 'code' => 'MAIN', 'is_active' => true]);

            foreach ($catalog['products'] as $row) {
                $keys = array_column($row['sources'], 'key');
                $known = DB::table('catalog_import_sources')->whereIn('source_key', $keys)->pluck('product_id')->unique();
                if ($known->count() > 1) {
                    throw new RuntimeException('Conflicting existing products for '.implode(', ', $keys));
                }
                if ($known->isNotEmpty()) {
                    // Reimports must not overwrite later admin edits, dates, prices, or inventory.
                    $product = Product::query()->where('id', $known->first())->firstOrFail();
                    $skipped++;
                } else {
                    $sku = $prefix.'-'.str_pad((string) $nextSku++, 6, '0', STR_PAD_LEFT);
                    $slug = $this->uniqueSlug('products', $row['name'], strtolower($sku));
                    $product = Product::create([
                        'name' => $row['name'],
                        'slug' => $slug,
                        'sku' => $sku,
                        'category_id' => $this->categoryId($row['category_path'] ?: ['سایر محصولات']),
                        'description' => $row['description'],
                        'short_description' => $row['short_description'] ?? $row['name'],
                        'main_image' => $row['main_image'] ?? null,
                        'expiry_date' => null,
                        'brand_id' => null,
                        'barcode' => null,
                        'product_type' => null,
                        'unit' => null,
                        'quantity_per_unit' => null,
                        'specifications' => null,
                        'canonical_url' => null,
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => 0,
                    ]);
                    Inventory::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => 10,
                        'minimum_quantity' => 0,
                        'batch_number' => null,
                        'expiry_date' => null,
                        'is_active' => true,
                    ]);
                    $created++;
                    if ($created % 1000 === 0) {
                        $this->line('Imported '.$created.' products.');
                    }
                }
                foreach ($row['sources'] as $source) {
                    DB::table('catalog_import_sources')->insertOrIgnore([
                        'product_id' => $product->id,
                        'source_key' => $source['key'],
                        'source_url' => $source['url'],
                        'metadata' => json_encode([
                            'quality' => $source['quality'] ?? null,
                            'description_source' => $row['description_source'] ?? null,
                            'description_mode' => $row['description_mode'],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return compact('created', 'skipped');
        });
        $this->info('Created: '.$result['created'].'; existing (preserved): '.$result['skipped']);
        $this->line('Initial stock: 10. Product and inventory expiry: empty. No source prices or barcodes imported.');

        return self::SUCCESS;
    }

    private function normalize(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strtr($name, ['ي' => 'ی', 'ك' => 'ک', '‌' => ' '])));
    }

    private function categoryId(array $path): ?int
    {
        $parentId = null;
        foreach ($path as $name) {
            $name = $this->normalize($name);
            $key = ($parentId ?? 0).'|'.$name;
            if (! isset($this->categories[$key])) {
                $category = Category::create([
                    'parent_id' => $parentId,
                    'name' => $name,
                    'slug' => $this->uniqueSlug('categories', $name, 'category-'.substr(hash('sha256', $key), 0, 12)),
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
                $this->categories[$key] = $category->id;
            }
            $parentId = $this->categories[$key];
        }

        return $parentId;
    }

    private function uniqueSlug(string $table, string $name, string $fallback): string
    {
        $base = Str::slug($name) ?: $fallback;
        $base = Str::limit($base, 220, '');
        $slug = $base;
        for ($suffix = 2; DB::table($table)->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
