<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from') ?? now()->startOfMonth()->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $orders = Order::query()->whereBetween('created_at', [$from, $to]);
        $payments = Payment::query()->where('status', 'paid')->whereBetween('paid_at', [$from, $to]);

        $ordersCount = (clone $orders)->count();
        $cancelledCount = (clone $orders)->where('status', 'cancelled')->count();
        $completedOrdersCount = $ordersCount - $cancelledCount;
        $grossSales = (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('total_amount');
        $successfulPayments = (float) (clone $payments)->sum('amount');
        $averageOrder = $completedOrdersCount > 0 ? $grossSales / $completedOrdersCount : 0;

        $itemsSold = (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereBetween('created_at', [$from, $to])
                    ->where('status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $topProducts = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as quantity, SUM(line_total) as sales')
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereBetween('created_at', [$from, $to])
                    ->where('status', '!=', 'cancelled');
            })
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $productData = [];
        foreach ($topProducts as $item) {
            $productData[] = [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'محصول حذف‌شده',
                'quantity' => (int) $item->quantity,
                'sales' => (float) $item->getAttribute('sales'),
            ];
        }

        return Inertia::render('Admin/Reports/Index', [
            'summary' => [
                'orders_count' => $ordersCount,
                'cancelled_count' => $cancelledCount,
                'gross_sales' => $grossSales,
                'successful_payments' => $successfulPayments,
                'items_sold' => $itemsSold,
                'average_order' => $averageOrder,
            ],
            'top_products' => $productData,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
