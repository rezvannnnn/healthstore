<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));
        $gateway = trim((string) $request->query('gateway', 'all'));

        $query = Payment::query()
            ->with('order:id,order_number,user_id')
            ->with('order.user:id,name,phone')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('authority', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orderQuery) use ($search): void {
                        $orderQuery->where('order_number', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search): void {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($gateway !== 'all') {
            $query->where('gateway', $gateway);
        }

        $paginator = $query->paginate(20)->withQueryString();
        $payments = [];

        foreach ($paginator->getCollection() as $payment) {
            $order = $payment->order;
            $user = $order?->user;

            $payments[] = [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $order?->order_number,
                'customer_name' => $user?->getAttribute('name'),
                'customer_phone' => $user?->getAttribute('phone'),
                'amount' => (float) $payment->amount,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'reference_number' => $payment->reference_number,
                'created_at' => $payment->created_at?->toISOString(),
                'paid_at' => $payment->paid_at?->toISOString(),
            ];
        }

        $gateways = Payment::query()
            ->whereNotNull('gateway')
            ->where('gateway', '!=', '')
            ->distinct()
            ->orderBy('gateway')
            ->pluck('gateway')
            ->all();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'gateway' => $gateway,
            ],
            'gateways' => $gateways,
        ]);
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['order.user', 'order.address', 'order.items.product']);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment,
        ]);
    }
}
