<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerPaymentDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_order_page_does_not_expose_payment_secrets(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-PAY-DATA-'.uniqid(),
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 120000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 120000,
            'currency' => 'IRR',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 120000,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => 'SECRET-AUTHORITY',
            'transaction_id' => 'TX-123',
            'reference_number' => 'REF-123',
            'card_token' => 'SECRET-CARD-TOKEN',
            'gateway_response' => ['secret' => 'should-not-be-exposed'],
        ]);

        $response = $this->actingAs($user)->get('/orders/'.$order->order_number);

        $response->assertInertia(function ($page) {
            $page->component('Order/Show')
                ->has('order.payments', 1)
                ->missing('order.payments.0.authority')
                ->missing('order.payments.0.card_token')
                ->missing('order.payments.0.gateway_response')
                ->where('order.payments.0.transaction_id', 'TX-123')
                ->where('order.payments.0.reference_number', 'REF-123');
        });
    }
}
