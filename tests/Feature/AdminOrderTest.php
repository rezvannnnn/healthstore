<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        return Order::create([
            'order_number' => 'ORD-ADMIN-'.uniqid(),
            'user_id' => $user->id,
            'address_id' => null,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => 200000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 200000,
            'currency' => 'IRR',
            'recipient_name' => $user->name,
            'recipient_phone' => $user->phone,
            'shipping_address' => null,
        ]);
    }

    public function test_admin_can_view_order_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders')
                ->has('pagination')
                ->has('filters')
            );
    }

    public function test_non_admin_cannot_view_order_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/orders')->assertForbidden();
    }

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $order = $this->order($customer);
        $product = Product::create([
            'name' => 'Admin Order Product',
            'slug' => 'admin-order-product',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => null,
            'quantity' => 2,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'total_amount' => 200000,
        ]);

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.order_number', $order->order_number)
                ->has('order.items', 1)
            );
    }

    public function test_admin_can_move_paid_order_to_processing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $order = $this->order($customer, 'paid', 'paid');

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_admin_cannot_process_unpaid_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $order = $this->order($customer, 'pending', 'pending');

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }
}
