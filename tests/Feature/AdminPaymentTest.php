<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-PAY-'.uniqid(),
            'user_id' => $user->id,
            'address_id' => null,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 150000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 150000,
            'currency' => 'IRR',
            'recipient_name' => $user->name,
            'recipient_phone' => $user->phone,
            'shipping_address' => null,
        ]);
    }

    public function test_admin_can_view_payment_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/payments')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments/Index')
                ->has('payments')
                ->has('pagination')
                ->has('filters')
                ->has('gateways')
            );
    }

    public function test_non_admin_cannot_view_payment_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/payments')->assertForbidden();
    }

    public function test_admin_can_filter_payments_by_status_and_search(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['name' => 'Payment Customer']);
        $order = $this->order($customer);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 150000,
            'gateway' => 'zarinpal',
            'status' => 'paid',
            'authority' => 'AUTH-123',
            'transaction_id' => 'TX-123',
            'reference_number' => 'REF-123',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/payments?status=paid&search=TX-123')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments/Index')
                ->has('payments', 1)
                ->where('payments.0.transaction_id', 'TX-123')
                ->where('filters.status', 'paid')
            );
    }

    public function test_admin_can_view_payment_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $order = $this->order($customer);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 150000,
            'gateway' => 'zarinpal',
            'status' => 'paid',
            'transaction_id' => 'TX-DETAIL',
            'reference_number' => 'REF-DETAIL',
            'gateway_response' => ['code' => 100],
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/admin/payments/{$payment->id}")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments/Show')
                ->where('payment.id', $payment->id)
                ->where('payment.transaction_id', 'TX-DETAIL')
            );
    }
}
