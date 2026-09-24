<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
        protected MediaService $mediaService,
    ) {}

    public function index(Request $request): Response
    {
        $validatedFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validatedFilters['search'] ?? ''));
        $categorySlug = trim((string) ($validatedFilters['category'] ?? ''));
        $brandSlug = trim((string) ($validatedFilters['brand'] ?? ''));

        $query = Product::query()
            ->with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', function ($builder) use ($categorySlug) {
                $builder->where('slug', $categorySlug);
            });
        }

        if ($brandSlug !== '') {
            $query->whereHas('brand', function ($builder) use ($brandSlug) {
                $builder
                    ->where('slug', $brandSlug)
                    ->where('is_active', true);
            });
        }

        $paginator = $query->paginate(12)->withQueryString();

        $products = collect($paginator->items())->map(function (Product $product) {
            $price = $this->cartService->getCurrentPrice($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'brand' => $product->brand?->name,
                'brand_slug' => $product->brand?->slug,
                'category' => $product->category?->name,
                'image' => $this->mediaService->url($product->main_image ?: $product->images->firstWhere('is_primary', true)?->image_path ?: $product->images->first()?->image_path),
                'price' => $price?->price !== null ? (float) $price->price : null,
                'compare_at_price' => $price?->compare_at_price !== null ? (float) $price->compare_at_price : null,
                'available' => $this->inventoryService->isAvailable($product),
            ];
        })->values()->all();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Product/Index', [
            'seo' => [
                'title' => 'محصولات | فروشگاه سلامت',
                'description' => 'خرید و بررسی محصولات بهداشتی و سلامت از فروشگاه آنلاین.',
                'canonical' => url('/products'),
            ],
            'products' => $products,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'categories' => $categories,
            'brands' => $brands,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'brand' => $brandSlug,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category', 'images', 'prices']);
        $price = $this->cartService->getCurrentPrice($product);

        $relatedProducts = Product::query()
            ->with(['brand', 'images'])
            ->where('is_active', true)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->where('id', '!=', $product->id)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(4)
            ->get()
            ->map(function (Product $related) {
                $relatedPrice = $this->cartService->getCurrentPrice($related);

                return [
                    'id' => $related->id,
                    'name' => $related->name,
                    'slug' => $related->slug,
                    'image' => $this->mediaService->url($related->main_image ?: $related->images->firstWhere('is_primary', true)?->image_path ?: $related->images->first()?->image_path),
                    'price' => $relatedPrice?->price !== null ? (float) $relatedPrice->price : null,
                ];
            })
            ->values()
            ->all();

        $images = $product->images
            ->sortBy([['is_primary', 'desc'], ['sort_order', 'asc']])
            ->values();
        $imagePaths = $images->pluck('image_path')->filter()->values();
        if ($product->main_image && ! $imagePaths->contains($product->main_image)) {
            $imagePaths->prepend($product->main_image);
        }

        $productUrl = $product->canonical_url ?: url('/products/'.$product->slug);
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->seo_description ?: $product->short_description,
            'url' => $productUrl,
        ];

        if ($product->sku) {
            $structuredData['sku'] = $product->sku;
        }

        if ($product->brand?->name) {
            $structuredData['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        if ($product->category?->name) {
            $structuredData['category'] = $product->category->name;
        }

        if ($imagePaths->isNotEmpty()) {
            $structuredData['image'] = $imagePaths->map(fn (string $path) => $this->mediaService->url($path))->filter()->values()->all();
        }

        if ($price?->price !== null) {
            $structuredData['offers'] = [
                '@type' => 'Offer',
                'price' => (float) $price->price,
                'priceCurrency' => 'IRR',
                'availability' => $this->inventoryService->isAvailable($product)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => $productUrl,
            ];
        }

        return Inertia::render('Product/Show', [
            'seo' => [
                'title' => $product->seo_title ?: $product->name,
                'description' => $product->seo_description ?: $product->short_description,
                'canonical' => $productUrl,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'product_type' => $product->product_type,
                'unit' => $product->unit,
                'quantity_per_unit' => $product->quantity_per_unit,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'specifications' => $product->specifications,
                'brand' => $product->brand?->name,
                'brand_slug' => $product->brand?->slug,
                'category' => $product->category?->name,
                'category_slug' => $product->category?->slug,
                'image' => $this->mediaService->url($product->main_image),
                'images' => $images->map(fn ($image) => [
                    'id' => $image->id,
                    'path' => $this->mediaService->url($image->image_path),
                    'alt' => $image->alt_text ?: $product->name,
                ])->all(),
                'price' => $price?->price !== null ? (float) $price->price : null,
                'compare_at_price' => $price?->compare_at_price !== null ? (float) $price->compare_at_price : null,
                'available_quantity' => $this->inventoryService->getAvailableQuantity($product),
                'available' => $this->inventoryService->isAvailable($product),
            ],
            'structuredData' => $structuredData,
            'relatedProducts' => $relatedProducts,
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
