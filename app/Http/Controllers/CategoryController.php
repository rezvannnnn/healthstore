<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
    ) {}

    public function index(): Response
    {
        $paginator = Category::query()
            ->with([
                'parent:id,name,slug,is_active',
                'children' => fn ($query) => $query
                    ->select(['id', 'parent_id', 'name', 'slug'])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->withCount([
                'products as active_products_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(24);

        $categories = collect($paginator->items())
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $this->absoluteAssetUrl($category->image),
                'products_count' => (int) $category->getAttribute('active_products_count'),
                'parent' => $category->parent && $category->parent->is_active
                    ? $category->parent->only(['id', 'name', 'slug'])
                    : null,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $structuredCategories = collect($categories)
            ->map(fn (array $category, int $index) => [
                '@type' => 'ListItem',
                'position' => ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1,
                'url' => route('categories.show', $category['slug']),
                'name' => $category['name'],
            ])
            ->all();

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'دسته‌بندی‌ها | فروشگاه سلامت',
            'description' => 'مشاهده دسته‌بندی‌های فعال و محصولات هر دسته در فروشگاه سلامت.',
            'url' => route('categories.index'),
        ];

        if ($structuredCategories !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredCategories),
                'itemListElement' => $structuredCategories,
            ];
        }

        return Inertia::render('Category/Index', [
            'seo' => [
                'title' => 'دسته‌بندی‌ها | فروشگاه سلامت',
                'description' => 'مشاهده دسته‌بندی‌های فعال و محصولات هر دسته در فروشگاه سلامت.',
                'canonical' => route('categories.index'),
            ],
            'categories' => $categories,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'structuredData' => $structuredData,
        ]);
    }

    public function show(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,name,slug,is_active',
            'children' => fn ($query) => $query
                ->select(['id', 'parent_id', 'name', 'slug'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name'),
        ]);

        $paginator = Product::query()
            ->with(['brand', 'images'])
            ->where('is_active', true)
            ->where('category_id', $category->id)
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

        $categoryUrl = route('categories.show', $category);
        $description = $category->description
            ?: 'خرید و بررسی محصولات دسته '.$category->name.' از فروشگاه آنلاین سلامت.';

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
            'name' => $category->name,
            'description' => $description,
            'url' => $categoryUrl,
        ];

        if ($structuredProducts !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredProducts),
                'itemListElement' => $structuredProducts,
            ];
        }

        return Inertia::render('Category/Show', [
            'seo' => [
                'title' => $category->name.' | فروشگاه سلامت',
                'description' => $description,
                'canonical' => $categoryUrl,
            ],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'parent' => $category->parent && $category->parent->is_active
                    ? $category->parent->only(['id', 'name', 'slug'])
                    : null,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                ])->values()->all(),
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
    protected function absoluteAssetUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url(ltrim($path, '/'));
    }

}
