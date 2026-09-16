<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $cart = $this->cartService->getCartForUser($user->id);
        $cart->load('items.product.images', 'items.product.brand');

        return Inertia::render('Cart/Index', [
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->unit_price * $item->quantity,
                    'product' => [
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'brand' => $item->product->brand?->name,
                        'image' => $item->product->main_image ?: $item->product->images->firstWhere('is_primary', true)?->image_path ?: $item->product->images->first()?->image_path,
                    ],
                ])->values()->all(),
                'subtotal' => $this->cartService->calculateSubtotal($cart),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $this->cartService->addItem(
                $user->id,
                (int) $validated['product_id'],
                (int) ($validated['quantity'] ?? 1)
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['product_id' => $e->getMessage()]);
        }

        return back()->with('success', 'محصول به سبد خرید اضافه شد.');
    }

    public function update(Request $request, Cart $cart, int $item): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ((int) $cart->user_id !== (int) $user->id || $cart->status !== 'active') {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $lock = Cache::lock("cart:user:{$user->id}", 30);

        if (! $lock->get()) {
            return back()->withErrors([
                'quantity' => 'عملیات دیگری روی سبد خرید شما در حال انجام است. لطفاً چند لحظه صبر کنید.',
            ]);
        }

        try {
            try {
                $cartItem = $cart->items()->whereKey($item)->firstOrFail();
                $product = $cartItem->product;

                if (! $product || ! $product->is_active) {
                    throw new RuntimeException('محصول مورد نظر موجود نیست یا غیرفعال شده است.');
                }

                $availableQuantity = $this->inventoryService->getAvailableQuantity($product);

                if ((int) $validated['quantity'] > $availableQuantity) {
                    throw new RuntimeException(
                        "موجودی این محصول کافی نیست. حداکثر تعداد قابل انتخاب: {$availableQuantity}."
                    );
                }

                $price = $this->cartService->getCurrentPrice(
                    $product,
                    (int) $validated['quantity']
                );

                if (! $price) {
                    throw new RuntimeException('برای این تعداد، قیمت فعالی برای محصول ثبت نشده است.');
                }

                $cartItem->update([
                    'quantity' => (int) $validated['quantity'],
                    'unit_price' => $price->price,
                ]);
            } catch (RuntimeException $e) {
                return back()->withErrors(['quantity' => $e->getMessage()]);
            }
        } finally {
            $lock->release();
        }

        return back()->with('success', 'سبد خرید به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Cart $cart, int $item): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ((int) $cart->user_id !== (int) $user->id || $cart->status !== 'active') {
            abort(404);
        }

        $lock = Cache::lock("cart:user:{$user->id}", 30);

        if (! $lock->get()) {
            return back()->withErrors([
                'item' => 'عملیات دیگری روی سبد خرید شما در حال انجام است. لطفاً چند لحظه صبر کنید.',
            ]);
        }

        try {
            $cart->items()->whereKey($item)->delete();
        } finally {
            $lock->release();
        }

        return back()->with('success', 'محصول از سبد خرید حذف شد.');
    }
}
