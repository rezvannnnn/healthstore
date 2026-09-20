<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
    ) {}

    public function __invoke(): Response
    {
        $featuredProducts = Product::query()
            ->with(['brand', 'images'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (Product $product) {
                $price = $this->cartService->getCurrentPrice($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
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

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();

        $seo = [
            'title' => 'HealthStore | فروشگاه آنلاین محصولات سلامت',
            'description' => 'خرید آنلاین محصولات بهداشتی و سلامت با مشاهده محصولات منتخب، دسته‌بندی‌ها، موجودی و مسیر پرداخت یکپارچه.',
            'canonical' => route('home'),
        ];

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name', 'HealthStore'),
            'url' => route('home'),
            'inLanguage' => 'fa-IR',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('products.index').'?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return Inertia::render('Welcome', [
            'seo' => $seo,
            'structuredData' => $structuredData,
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }
}
