<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
    ) {}

    public function show(Brand $brand): Response
    {
        abort_unless($brand->is_active, 404);

        $paginator = Product::query()
            ->with(['brand', 'images'])
            ->where('is_active', true)
            ->where('brand_id', $brand->id)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(12);

        $products = collect($paginator->items())
            ->map(function (Product $product) {
                $price = $this->cartService->getCurrentPrice($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'short_description' => $product->short_description,
                    'image' => $product->main_image
                        ?: $product->images->firstWhere('is_primary', true)?->image_path
                        ?: $product->images->first()?->image_path,
                    'price' => $price?->price !== null ? (float) $price->price : null,
                    'compare_at_price' => $price?->compare_at_price !== null ? (float) $price->compare_at_price : null,
                    'available' => $this->inventoryService->isAvailable($product),
                ];
            })
            ->values()
            ->all();

        $brandUrl = route('brands.show', $brand);
        $description = $brand->description
            ?: 'خرید و بررسی محصولات برند '.$brand->name.' از فروشگاه آنلاین سلامت.';

        $structuredProducts = collect($products)
            ->map(fn (array $product, int $index) => [
                '@type' => 'ListItem',
                'position' => ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1,
                'url' => route('products.show', $product['slug']),
                'name' => $product['name'],
            ])
            ->all();

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $brand->name,
            'description' => $description,
            'url' => $brandUrl,
        ];

        if ($structuredProducts !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredProducts),
                'itemListElement' => $structuredProducts,
            ];
        }

        return Inertia::render('Brand/Show', [
            'seo' => [
                'title' => $brand->name.' | فروشگاه سلامت',
                'description' => $description,
                'canonical' => $brandUrl,
            ],
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'description' => $brand->description,
                'logo' => $brand->logo,
            ],
            'products' => $products,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'structuredData' => $structuredData,
        ]);
    }
}
