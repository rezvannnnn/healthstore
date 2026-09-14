<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zarinpal.merchant_id' => 'TEST-MERCHANT-ID',
            'services.zarinpal.request_endpoint' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
            'services.zarinpal.verify_endpoint' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
            'services.zarinpal.payment_base_url' => 'https://www.zarinpal.com',
            'services.zarinpal.callback_url' => 'http://127.0.0.1:8000/payment/zarinpal/callback',
        ]);
    }

    private function createOrder(User $user, string $status = 'pending', string $paymentStatus = 'pending'): Order
    {
        return Order::create([
            'order_number' => 'ORD-PERROR-'.now()->format('YmdHis').'-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => 220000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 220000,
            'currency' => 'IRR',
            'recipient_name' => null,
            'recipient_phone' => null,
            'province' => null,
            'city' => null,
            'shipping_address' => null,
            'postal_code' => null,
            'customer_note' => null,
            'admin_note' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function test_gateway_failure_returns_to_order_page_without_exposing_an_exception(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => -9,
                    'message' => 'Invalid merchant',
                ],
                'errors' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $response = $this->from('/orders/'.$order->order_number)
            ->actingAs($user)
            ->post('/orders/'.$order->order_number.'/payment');

        $response->assertRedirect('/orders/'.$order->order_number);
        $response->assertSessionHas('error');

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
    }

    public function test_cancelled_order_payment_request_returns_to_order_page(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $order = $this->createOrder($user, 'cancelled', 'cancelled');

        $response = $this->from('/orders/'.$order->order_number)
            ->actingAs($user)
            ->post('/orders/'.$order->order_number.'/payment');

        $response->assertRedirect('/orders/'.$order->order_number);
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }
}
