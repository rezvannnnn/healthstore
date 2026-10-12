<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CatalogTestRollback extends Command
{
    protected $signature = 'catalog:test-rollback';

    protected $description = 'Remove only the reversible catalog test import created by catalog:test-import';

    public function handle(): int
    {
        $manifestPath = $this->manifestPath();

        if (! is_file($manifestPath)) {
            $this->error('Manifest مربوط به Import آزمایشی پیدا نشد.');

            return self::FAILURE;
        }

        $manifest = json_decode(
            (string) file_get_contents($manifestPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $productIds = array_values(array_filter(array_map('intval', $manifest['products'] ?? [])));
        $brandIds = array_values(array_filter(array_map('intval', $manifest['brands'] ?? [])));
        $categoryIds = array_values(array_filter(array_map('intval', $manifest['categories'] ?? [])));
        $warehouseIds = array_values(array_filter(array_map('intval', $manifest['warehouses'] ?? [])));

        if ($productIds === []) {
            @unlink($manifestPath);

            $this->info('داده‌ای برای بازگردانی وجود نداشت.');

            return self::SUCCESS;
        }

        $hasReferences = InventoryReservation::query()
            ->whereIn('product_id', $productIds)
            ->exists();

        $hasReferences = $hasReferences
            || DB::table('cart_items')->whereIn('product_id', $productIds)->exists()
            || DB::table('order_items')->whereIn('product_id', $productIds)->exists();

        if ($hasReferences) {
            $this->error('حداقل یکی از محصولات تست وارد سبد، سفارش یا رزرو شده است؛ برای حفظ سوابق، Rollback متوقف شد.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($productIds, $brandIds, $categoryIds, $warehouseIds, $manifestPath): void {
            $inventoryIds = Inventory::query()
                ->whereIn('product_id', $productIds)
                ->pluck('id')
                ->all();

            if ($inventoryIds !== []) {
                InventoryMovement::query()->whereIn('inventory_id', $inventoryIds)->delete();
                InventoryReservation::query()->whereIn('inventory_id', $inventoryIds)->delete();
            }

            ProductImage::query()->whereIn('product_id', $productIds)->delete();
            ProductPrice::query()->whereIn('product_id', $productIds)->delete();
            Inventory::query()->whereIn('product_id', $productIds)->delete();
            Product::query()->whereIn('id', $productIds)->delete();

            foreach ($categoryIds as $categoryId) {
                $category = Category::query()->find($categoryId);

                if (! $category) {
                    continue;
                }

                if ($category->products()->exists() || $category->children()->exists()) {
                    throw new RuntimeException(
                        "دسته‌بندی تست {$category->name} هنوز وابستگی دارد و قابل حذف نیست.",
                    );
                }

                $category->delete();
            }

            foreach ($brandIds as $brandId) {
                $brand = Brand::query()->find($brandId);

                if (! $brand) {
                    continue;
                }

                if ($brand->products()->exists()) {
                    throw new RuntimeException(
                        "برند تست {$brand->name} هنوز محصول دارد و قابل حذف نیست.",
                    );
                }

                $brand->delete();
            }

            foreach ($warehouseIds as $warehouseId) {
                $warehouse = Warehouse::query()->find($warehouseId);

                if (! $warehouse) {
                    continue;
                }

                if ($warehouse->inventories()->exists()) {
                    throw new RuntimeException(
                        "انبار تست {$warehouse->name} هنوز موجودی دارد و قابل حذف نیست.",
                    );
                }

                $warehouse->delete();
            }

            @unlink($manifestPath);
        });

        $this->info('Import آزمایشی با موفقیت Rollback شد و داده‌های ایجادشده حذف شدند.');

        return self::SUCCESS;
    }

    protected function manifestPath(): string
    {
        return (string) config('catalog.test_manifest', storage_path('app/testing/catalog-test-import.json'));
    }
}
