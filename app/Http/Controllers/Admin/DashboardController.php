<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $todaySales = (float) Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $today)
            ->sum('total_amount');

        $monthSales = (float) Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $month)
            ->sum('total_amount');

        $lowStockProducts = DB::table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.is_active', true)
            ->where('products.is_active', true)
            ->groupBy('inventories.product_id', 'products.name')
            ->havingRaw('SUM(inventories.quantity) <= MIN(inventories.minimum_quantity)')
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'today_sales' => $todaySales,
                'month_sales' => $monthSales,
                'orders_count' => Order::query()->count(),
                'pending_orders' => Order::query()->where('status', 'pending')->count(),
                'processing_orders' => Order::query()->where('status', 'processing')->count(),
                'customers_count' => User::query()->where('is_admin', false)->count(),
                'products_count' => Product::query()->count(),
                'active_products_count' => Product::query()->where('is_active', true)->count(),
                'pending_payments' => Payment::query()->where('status', 'pending')->count(),
                'failed_payments' => Payment::query()->where('status', 'failed')->count(),
                'low_stock_products' => $lowStockProducts,
            ],
        ]);
    }
}
