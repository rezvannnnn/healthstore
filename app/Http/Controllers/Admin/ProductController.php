<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));

        $query = Product::query()
            ->with(['brand', 'category', 'images'])
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $paginator = $query->paginate(15)->withQueryString();

        $products = collect($paginator->items())->map(function (Product $product) {
            $price = $this->cartService->getCurrentPrice($product);
            $physical = $this->inventoryService->getPhysicalQuantity($product);
            $reserved = $this->inventoryService->getReservedQuantity($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'image' => $product->main_image ?: $product->images->firstWhere('is_primary', true)?->image_path,
                'price' => $price?->price !== null ? (float) $price->price : null,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'physical_quantity' => $physical,
                'reserved_quantity' => $reserved,
                'available_quantity' => max(0, $physical - $reserved),
            ];
        })->values()->all();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            ...$this->formOptions(),
            'product' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data): void {
            $product = Product::create($this->productData($data));
            $this->syncRetailPrice($product, $data);
        });

        return to_route('admin.products.index')->with('success', 'محصول با موفقیت ایجاد شد.');
    }

    public function edit(Product $product): Response
    {
        $product->load(['brand', 'category', 'prices']);
        $price = $product->prices
            ->where('price_type', 'retail')
            ->where('min_quantity', 1)
            ->sortByDesc('id')
            ->first();

        return Inertia::render('Admin/Products/Edit', [
            ...$this->formOptions(),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'product_type' => $product->product_type,
                'unit' => $product->unit,
                'quantity_per_unit' => $product->quantity_per_unit,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'expiry_date' => $product->expiry_date?->format('Y-m-d'),
                'main_image' => $product->main_image,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'sort_order' => $product->sort_order,
                'price' => $price?->price !== null ? (float) $price->price : null,
                'compare_at_price' => $price?->compare_at_price !== null ? (float) $price->compare_at_price : null,
            ],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);

        DB::transaction(function () use ($data, $product): void {
            $product->update($this->productData($data));
            $this->syncRetailPrice($product, $data);
        });

        return to_route('admin.products.index')->with('success', 'محصول با موفقیت ویرایش شد.');
    }

    protected function formOptions(): array
    {
        return [
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product?->id)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:255'],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'product_type' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'quantity_per_unit' => ['nullable', 'integer', 'min:1'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'main_image' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price'],
        ]);
    }

    protected function productData(array $data): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));

        return [
            'name' => $data['name'],
            'slug' => $slug !== '' ? $slug : Str::slug($data['name']),
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'product_type' => $data['product_type'] ?? null,
            'unit' => $data['unit'] ?? null,
            'quantity_per_unit' => $data['quantity_per_unit'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'main_image' => $data['main_image'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    protected function syncRetailPrice(Product $product, array $data): void
    {
        ProductPrice::updateOrCreate(
            [
                'product_id' => $product->id,
                'price_type' => 'retail',
                'min_quantity' => 1,
            ],
            [
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
