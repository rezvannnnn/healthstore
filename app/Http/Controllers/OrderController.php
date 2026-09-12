<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Show the authenticated customer's order history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->select([
                'id',
                'user_id',
                'order_number',
                'status',
                'payment_status',
                'subtotal',
                'discount_amount',
                'shipping_amount',
                'total_amount',
                'currency',
                'created_at',
                'updated_at',
                'paid_at',
                'shipped_at',
                'delivered_at',
                'cancelled_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render(
            'Account/Orders/Index',
            [
                'orders' => $orders,
            ]
        );
    }

    /**
     * Show an order to its owner.
     */
    public function show(
        Request $request,
        string $orderNumber
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with([
                'items',
                'payments',
                'inventoryReservations',
            ])
            ->firstOrFail();

        return Inertia::render('Order/Show', [
            'order' => $order,
            'success' => session('success'),
            'error' => session('error'),
            'info' => session('info'),
        ]);
    }

    /**
     * Cancel the authenticated customer's own pending order.
     */
    public function cancel(
        Request $request,
        string $orderNumber
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user, 401);

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            $this->orderService->cancel($order);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'order' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'سفارش با موفقیت لغو شد.'
        );
    }
}
