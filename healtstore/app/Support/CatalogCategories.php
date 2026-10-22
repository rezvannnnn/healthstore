<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class CatalogCategories
{
    /** @return array<int, int> */
    public static function descendantIds(int $categoryId): array
    {
        $children = [];
        foreach (DB::table('categories')->select('id', 'parent_id')->get() as $category) {
            $children[(int) ($category->parent_id ?? 0)][] = (int) $category->id;
        }
        $ids = [];
        $pending = [$categoryId];
        while ($pending !== []) {
            $id = array_pop($pending);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = $id;
            foreach ($children[$id] ?? [] as $child) {
                $pending[] = $child;
            }
        }

        return array_values($ids);
    }

    /** @return array<int, int> */
    public static function activeProductCounts(): array
    {
        $parents = DB::table('categories')->pluck('parent_id', 'id')->all();
        $counts = [];
        $direct = DB::table('products')->where('is_active', true)->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as total')->groupBy('category_id')->get();
        foreach ($direct as $row) {
            $id = (int) $row->category_id;
            $seen = [];
            while ($id && ! isset($seen[$id])) {
                $seen[$id] = true;
                $counts[$id] = ($counts[$id] ?? 0) + (int) $row->total;
                $id = (int) ($parents[$id] ?? 0);
            }
        }

        return $counts;
    }
}
