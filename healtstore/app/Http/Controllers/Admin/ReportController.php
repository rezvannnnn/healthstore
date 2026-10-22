<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $timezone = StoreSetting::getValue('timezone', 'Asia/Tehran');
        $localNow = now()->setTimezone($timezone);
        $from = Carbon::parse($request->input('from') ?: $localNow->startOfMonth()->toDateString(), $timezone)->startOfDay();
        $to = Carbon::parse($request->input('to') ?: now()->setTimezone($timezone)->toDateString(), $timezone)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $from = $from->utc();
        $to = $to->utc();
        $orders = Order::query()->whereBetween('created_at', [$from, $to]);
        $salesOrders = Order::query()->whereBetween('paid_at', [$from, $to])
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');
        $payments = Payment::query()->where('status', 'paid')->whereBetween('paid_at', [$from, $to]);

        $ordersCount = (clone $orders)->count();
        $cancelledCount = (clone $orders)->where('status', 'cancelled')->count();
        $paidOrdersCount = (clone $salesOrders)->count();
        $grossSales = (float) (clone $salesOrders)->sum('total_amount');
        $successfulPayments = (float) (clone $payments)->sum('amount');
        $averageOrder = $paidOrdersCount > 0 ? $grossSales / $paidOrdersCount : 0;

        $itemsSold = (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereBetween('paid_at', [$from, $to])
                    ->where('payment_status', 'paid')
                    ->where('status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $topProducts = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as quantity, SUM(total_amount) as sales')
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereBetween('paid_at', [$from, $to])
                    ->where('payment_status', 'paid')
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

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($productData, $grossSales, $successfulPayments, $fromDate, $toDate): void {
                $stream = fopen('php://output', 'w');
                if ($stream === false) {
                    throw new \RuntimeException('Cannot open export output');
                }
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['از', 'تا', 'فروش پرداخت‌شده (ریال)', 'پرداخت موفق (ریال)'], ',', '"', '');
                fputcsv($stream, [$fromDate, $toDate, $grossSales, $successfulPayments], ',', '"', '');
                fputcsv($stream, ['محصول', 'تعداد', 'مبلغ اقلام پیش از تخفیف و ارسال (ریال)'], ',', '"', '');
                foreach ($productData as $row) {
                    $name = preg_match('/^[=+@\-\t\r\n]/', $row['name']) ? "'".$row['name'] : $row['name'];
                    fputcsv($stream, [$name, $row['quantity'], $row['sales']], ',', '"', '');
                }
                fclose($stream);
            }, 'sales-'.$fromDate.'-'.$toDate.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return Inertia::render('Admin/Reports/Index', [
            'summary' => [
                'orders_count' => $ordersCount,
                'cancelled_count' => $cancelledCount,
                'paid_orders_count' => $paidOrdersCount,
                'gross_sales' => $grossSales,
                'products_subtotal' => (float) (clone $salesOrders)->sum('subtotal'),
                'discounts' => (float) (clone $salesOrders)->sum('discount_amount'),
                'shipping' => (float) (clone $salesOrders)->sum('shipping_amount'),
                'requires_review' => Payment::query()->where('status', 'requires_review')->count(),
                'date_basis' => 'فروش و پرداخت بر اساس تاریخ پرداخت؛ تعداد سفارش‌ها بر اساس تاریخ ایجاد. مبالغ اقلام پرفروش پیش از تخفیف و ارسال هستند.',
                'successful_payments' => $successfulPayments,
                'items_sold' => $itemsSold,
                'average_order' => $averageOrder,
            ],
            'top_products' => $productData,
            'filters' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ]);
    }
}
