<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOrderFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $customer, string $number = 'ORD-FILTER'): void
    {
        Order::create([
            'order_number' => $number,
            'user_id' => $customer->id,
            'customer_type' => 'b2c',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 1000,
            'currency' => 'IRR',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
        ]);
    }

    public function test_invalid_order_status_filter_returns_no_results(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $this->createOrder($customer, 'ORD-FILTER-1');

        $this->actingAs($admin)
            ->get('/admin/orders?status=invalid-status')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->where('filters.status', 'invalid-status')
                ->has('orders', 0)
            );
    }

    public function test_invalid_payment_status_filter_returns_no_results(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $this->createOrder($customer, 'ORD-FILTER-2');

        $this->actingAs($admin)
            ->get('/admin/orders?payment_status=invalid-payment-status')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->where('filters.payment_status', 'invalid-payment-status')
                ->has('orders', 0)
            );
    }
}
