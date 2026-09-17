<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk();
    }

    public function test_non_admin_cannot_view_sales_report(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/reports');

        $response->assertForbidden();
    }

    public function test_invalid_report_dates_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/reports?from=not-a-date&to=2026-09-14')
            ->assertSessionHasErrors('from');
    }

    public function test_report_counts_only_paid_orders_as_sales_and_top_products(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['is_admin' => false]);
        $product = Product::create([
            'name' => 'محصول گزارش',
            'slug' => 'report-product',
            'price' => 100000,
            'is_active' => true,
        ]);

        $paidOrder = Order::create([
            'order_number' => 'REP-1001',
            'user_id' => $customer->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 200000,
            'currency' => 'IRR',
            'recipient_name' => 'Test Customer',
            'recipient_phone' => '09120000000',
            'shipping_address' => 'Test address',
        ]);

        OrderItem::create([
            'order_id' => $paidOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100000,
            'quantity' => 2,
            'total_amount' => 200000,
        ]);

        Payment::create([
            'order_id' => $paidOrder->id,
            'amount' => 200000,
            'gateway' => 'test',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $pendingOrder = Order::create([
            'order_number' => 'REP-1002',
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 500000,
            'currency' => 'IRR',
            'recipient_name' => 'Test Customer',
            'recipient_phone' => '09120000000',
            'shipping_address' => 'Test address',
        ]);

        OrderItem::create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100000,
            'quantity' => 5,
            'total_amount' => 500000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports?from='.now()->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/Index')
            ->where('summary.orders_count', 2)
            ->where('summary.paid_orders_count', 1)
            ->where('summary.gross_sales', 200000)
            ->where('summary.successful_payments', 200000)
            ->where('summary.items_sold', 2)
            ->where('summary.average_order', 200000)
            ->where('top_products.0.name', 'محصول گزارش')
            ->where('top_products.0.quantity', 2)
            ->where('top_products.0.sales', 200000));
    }
}
