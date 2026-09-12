<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
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
}
