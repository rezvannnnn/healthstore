<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AdminInventoryService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AdminInventoryService $adminInventoryService,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));

        $productsQuery = Product::query()->orderByDesc('id');

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($status === 'out') {
            $productsWithStock = Inventory::query()
                ->select('product_id')
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->where(function ($query): void {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->groupBy('product_id');

            $productsQuery->whereNotIn('id', $productsWithStock);
        } elseif ($status === 'low') {
            $lowStockProductIds = Inventory::query()
                ->select('product_id')
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) > 0')
                ->havingRaw('SUM(quantity) <= SUM(minimum_quantity)');

            $productsQuery->whereIn('id', $lowStockProductIds);
        }

        $paginator = $productsQuery->paginate(20)->withQueryString();

        $products = collect($paginator->items())->map(function (Product $product): array {
            $physical = $this->inventoryService->getPhysicalQuantity($product);
            $reserved = $this->inventoryService->getReservedQuantity($product);
            $minimum = (int) Inventory::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->sum('minimum_quantity');
            $inventory = Inventory::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->orderBy('expiry_date')
                ->orderBy('id')
                ->first(['id']);

            return [
                'id' => $product->id,
                'inventory_id' => $inventory?->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'physical_quantity' => $physical,
                'reserved_quantity' => $reserved,
                'available_quantity' => max(0, $physical - $reserved),
                'minimum_quantity' => $minimum,
                'status' => $physical <= 0 ? 'out' : ($physical <= $minimum ? 'low' : 'ok'),
            ];
        });

        return Inertia::render('Admin/Inventory/Index', [
            'products' => $products->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_quantity' => ['nullable', 'integer', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        DB::transaction(function () use ($data): void {
            Inventory::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $data['quantity'],
                'minimum_quantity' => $data['minimum_quantity'] ?? 0,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_active' => true,
            ]);
        });

        return to_route('admin.inventory.index')->with('success', 'رکورد موجودی با موفقیت ایجاد شد.');
    }

    public function adjust(Request $request, Inventory $inventory): RedirectResponse
    {
        $data = $request->validate([
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->adminInventoryService->adjust(
                $inventory,
                (int) $data['quantity_delta'],
                $data['note'] ?? null,
                $request->user()?->id,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['quantity_delta' => $exception->getMessage()]);
        }

        return back()->with('success', 'موجودی با موفقیت اصلاح شد.');
    }

    public function movements(Inventory $inventory): Response
    {
        $inventory->load(['product', 'warehouse']);

        /** @var Product $product */
        $product = $inventory->product;

        /** @var Warehouse $warehouse */
        $warehouse = $inventory->warehouse;

        $movements = InventoryMovement::query()
            ->with('user:id,name')
            ->where('inventory_id', $inventory->id)
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Movements', [
            'inventory' => [
                'id' => $inventory->id,
                'product' => $product->name,
                'warehouse' => $warehouse->name,
                'quantity' => $inventory->quantity,
            ],
            'movements' => $movements,
        ]);
    }
}
