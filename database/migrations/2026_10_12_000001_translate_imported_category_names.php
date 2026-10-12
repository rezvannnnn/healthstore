<?php

use App\Support\CatalogCategoryNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Keep existing URLs and update only unchanged labels from the known import.
        DB::transaction(function (): void {
            foreach (CatalogCategoryNames::TRANSLATIONS as $english => $persian) {
                DB::table('categories')->where('slug', 'category-'.Str::slug($english))->where('name', $english)->update(['name' => $persian]);
            }
        });
    }

    public function down(): void
    {
        foreach (CatalogCategoryNames::TRANSLATIONS as $english => $persian) {
            DB::table('categories')->where('slug', 'category-'.Str::slug($english))->where('name', $persian)->update(['name' => $english]);
        }
    }
};
