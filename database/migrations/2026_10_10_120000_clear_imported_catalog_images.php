<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skus = array_map(
            fn (int $row): string => 'TEST-IMPORT-'.str_pad((string) $row, 3, '0', STR_PAD_LEFT),
            range(1, 109),
        );

        DB::transaction(function () use ($skus): void {
            $products = DB::table('products')->whereIn('sku', $skus);
            $ids = (clone $products)->pluck('id');

            DB::table('product_images')->whereIn('product_id', $ids)->delete();
            $products->update(['main_image' => null]);
        });
    }

    public function down(): void
    {
        // Removed image references cannot be reconstructed on rollback.
    }
};
