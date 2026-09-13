<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-CUSTOMER-'.uniqid(),
            'user_id' => $user->id,
            'address_id' => null,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'paid',
            'payment_status' => 'paid',
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

    public function test_admin_can_view_customer_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Customer Example']);

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers')
                ->has('pagination')
                ->has('filters')
            );
    }

    public function test_non_admin_cannot_view_customer_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/customers')->assertForbidden();
    }

    public function test_admin_can_search_customers(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Searchable Customer']);
        User::factory()->create(['name' => 'Other Customer']);

        $this->actingAs($admin)
            ->get('/admin/customers?search=Searchable')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers', 1)
                ->where('customers.0.name', 'Searchable Customer')
            );
    }

    public function test_admin_can_view_customer_details_and_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['name' => 'Customer Detail']);
        $order = $this->order($customer);

        $this->actingAs($admin)
            ->get("/admin/customers/{$customer->id}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Show')
                ->where('customer.id', $customer->id)
                ->where('customer.name', 'Customer Detail')
                ->has('orders', 1)
                ->where('orders.0.id', $order->id)
            );
    }

    public function test_admin_user_is_not_exposed_as_customer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->where('pagination.total', 1)
                ->where('customers.0.id', $customer->id)
            );
    }
}
