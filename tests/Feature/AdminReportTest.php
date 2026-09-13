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

    public function test_report_counts_non_cancelled_sales_and_top_products(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['is_admin' => false]);
        $product = Product::create([
            'name' => 'محصول گزارش',
            'slug' => 'report-product',
            'price' => 100000,
            'is_active' => true,
        ]);

        $order = Order::create([
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
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100000,
            'quantity' => 2,
            'line_total' => 200000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 200000,
            'gateway' => 'test',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports?from='.now()->toDateString().'&to='.now()->toDateString());

        $response->assertOk();
        $response->assertSee('محصول گزارش');
        $response->assertSee('200000');
    }
}
