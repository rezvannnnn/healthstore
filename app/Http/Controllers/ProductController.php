<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\MediaService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'in_stock' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999', ...($request->filled('min_price') ? ['gte:min_price'] : [])],
            'sort' => ['nullable', Rule::in(['recommended', 'newest', 'price_asc', 'price_desc'])],
        ]);

        $search = strtr(trim((string) ($validatedFilters['search'] ?? '')), ['ي' => 'ی', 'ك' => 'ک', '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
        $categorySlug = trim((string) ($validatedFilters['category'] ?? ''));
        $brandSlug = trim((string) ($validatedFilters['brand'] ?? ''));
        $inStock = (bool) ($validatedFilters['in_stock'] ?? false);
        $sort = $validatedFilters['sort'] ?? 'recommended';
        $priceQuery = fn (QueryBuilder $price) => $price->from('product_prices')->select('price')
            ->whereColumn('product_id', 'products.id')->where('is_active', true)->where('min_quantity', '<=', 1)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('min_quantity')->orderByDesc('id')->limit(1);

        $query = Product::query()->forCatalog()
            ->with(['brand', 'category', 'images'])
            ->where('is_active', true)
            ->selectSub($priceQuery, 'catalog_price');

        if ($inStock) {
            $query->availableForCatalog();
        }
        foreach (['min_price' => '>=', 'max_price' => '<='] as $filter => $operator) {
            if (isset($validatedFilters[$filter])) {
                $query->getQuery()->where($priceQuery, $operator, $validatedFilters[$filter]);
            }
        }
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            // Products without a current price belong at the end in either direction.
            $query->orderByRaw('catalog_price IS NULL')->orderBy('catalog_price', $sort === 'price_asc' ? 'asc' : 'desc')->orderByDesc('id');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('id');
        } else {
            $query->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('id');
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereRaw("REPLACE(REPLACE(name, 'ي', 'ی'), 'ك', 'ک') LIKE ?", ["%{$search}%"])
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
                'canonical' => url('/products').($paginator->currentPage() > 1 ? '?page='.$paginator->currentPage() : ''),
                'robots' => ($search !== '' || $categorySlug !== '' || $brandSlug !== '' || $inStock || isset($validatedFilters['min_price']) || isset($validatedFilters['max_price']) || $sort !== 'recommended') ? 'noindex,follow' : 'index,follow',
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
                'in_stock' => $inStock,
                'min_price' => $validatedFilters['min_price'] ?? null,
                'max_price' => $validatedFilters['max_price'] ?? null,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category', 'images', 'prices']);
        $price = $this->cartService->getCurrentPrice($product);

        $relatedProducts = Product::query()->forCatalog()
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
                'expiry' => $this->inventoryService->getExpiryRange($product),
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
