<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createPayment(): void
    {
        $customer = User::factory()->create();

        $order = $customer->orders()->create([
            'order_number' => 'ORD-PAY-FILTER',
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

        Payment::create([
            'order_id' => $order->id,
            'amount' => 1000,
            'gateway' => 'test-gateway',
            'status' => 'pending',
        ]);
    }

    public function test_invalid_payment_status_filter_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->createPayment();

        $this->actingAs($admin)
            ->get('/admin/payments?status=invalid-status')
            ->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    public function test_oversized_payment_gateway_filter_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->createPayment();

        $this->actingAs($admin)
            ->get('/admin/payments?gateway='.str_repeat('x', 101))
            ->assertRedirect()
            ->assertSessionHasErrors('gateway');
    }
}
