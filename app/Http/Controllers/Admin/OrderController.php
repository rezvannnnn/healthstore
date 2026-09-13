<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));
        $paymentStatus = trim((string) $request->query('payment_status', 'all'));

        $query = Order::query()
            ->with('user:id,name,phone')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }

        $paginator = $query->paginate(20)->withQueryString();
        $orders = [];

        foreach ($paginator->items() as $order) {
            /** @var Order $order */
            $user = $order->user;
            $customerName = $order->recipient_name;
            $customerPhone = $order->recipient_phone;

            if ($user instanceof User) {
                $customerName = $user->name ?: $customerName;
                $customerPhone = $user->phone ?: $customerPhone;
            }

            $orders[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'items_count' => $order->items_count,
                'total_amount' => (float) $order->total_amount,
                'created_at' => $order->created_at?->toISOString(),
            ];
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['user', 'address', 'items.product', 'payments', 'inventoryReservations.product']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,paid,processing,shipped,delivered'],
        ]);

        try {
            $this->orderService->setStatus($order, $data['status']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('success', 'وضعیت سفارش با موفقیت تغییر کرد.');
    }
}
