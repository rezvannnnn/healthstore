<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->where('is_admin', false)
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(20)->withQueryString();
        $customers = [];

        foreach ($paginator->items() as $customer) {
            /** @var User $customer */
            $customers[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'phone_verified' => $customer->hasVerifiedPhone(),
                'orders_count' => $customer->orders_count,
                'created_at' => $customer->created_at?->toISOString(),
            ];
        }

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(User $user): Response
    {
        abort_if($user->isAdmin(), 404);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $orderData = [];

        foreach ($orders as $order) {
            $orderData[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'items_count' => $order->items_count,
                'total_amount' => (float) $order->total_amount,
                'created_at' => $order->created_at?->toISOString(),
            ];
        }

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'phone_verified' => $user->hasVerifiedPhone(),
                'created_at' => $user->created_at?->toISOString(),
            ],
            'orders' => $orderData,
        ]);
    }
}
