<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$ids = DB::table('catalog_import_sources')->distinct()->pluck('product_id');
$rows = Product::query()->whereIn('id', $ids)->with('inventories')->get();
$errors = [];
foreach ($rows as $row) {
    if (! preg_match('/^DKH-[0-9]{6,}$/', (string) $row->sku)) {
        $errors[] = ['id' => $row->id, 'error' => 'sku'];
    }
    if ($row->expiry_date !== null || $row->barcode !== null || $row->canonical_url !== null) {
        $errors[] = ['id' => $row->id, 'error' => 'unexpected_source_fields'];
    }
    if ($row->inventories->count() !== 1 || (int) $row->inventories->sum('quantity') !== 10 || $row->inventories->first()?->expiry_date !== null) {
        $errors[] = ['id' => $row->id, 'error' => 'inventory'];
    }
    if ($row->main_image && ! is_file(storage_path('app/public/'.$row->main_image))) {
        $errors[] = ['id' => $row->id, 'error' => 'missing_image_file'];
    }
}
$report = [
    'imported' => $rows->count(),
    'sources' => DB::table('catalog_import_sources')->count(),
    'total_products' => Product::count(),
    'categories' => Category::count(),
    'with_image' => $rows->whereNotNull('main_image')->count(),
    'with_description' => $rows->filter(fn ($row) => filled($row->description))->count(),
    'imported_price_rows' => ProductPrice::whereIn('product_id', $ids)->count(),
    'errors' => $errors,
];
file_put_contents(storage_path('app/catalog-import/verification.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($errors === [] && $report['imported_price_rows'] === 0 ? 0 : 1);
