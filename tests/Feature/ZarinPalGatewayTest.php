<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\ZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZarinPalGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zarinpal.merchant_id' => 'TEST-MERCHANT-ID',
            'services.zarinpal.sandbox' => true,
            'services.zarinpal.request_endpoint' =>
                'https://api.zarinpal.com/pg/v4/payment/request.json',
            'services.zarinpal.verify_endpoint' =>
                'https://api.zarinpal.com/pg/v4/payment/verify.json',
            'services.zarinpal.payment_base_url' =>
                'https://www.zarinpal.com',
            'services.zarinpal.callback_url' =>
                'http://127.0.0.1:8000/payment/zarinpal/callback',
        ]);
    }

    private function createOrder(
        User $user,
        int $totalAmount = 220000
    ): Order {
        return Order::create([
            'order_number' => 'ORD-ZP-' . now()->format('YmdHis') . '-' . uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => $totalAmount,
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

    private function createPayment(
        Order $order,
        int $amount = 220000
    ): Payment {
        return $order->payments()->create([
            'amount' => $amount,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => null,
            'transaction_id' => null,
            'reference_number' => null,
            'card_last_four' => null,
            'card_token' => null,
            'gateway_response' => null,
            'paid_at' => null,
            'refunded_at' => null,
        ]);
    }

    public function test_gateway_request_sends_correct_payload_and_returns_authority(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' =>
                Http::response([
                    'data' => [
                        'code' => 100,
                        'message' => 'Success',
                        'authority' => 'S000000000000000000000000000001234',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            220000
        );

        $gateway = new ZarinPalGateway();

        $result = $gateway->request($payment);

        $this->assertTrue(
            $result['success']
        );

        $this->assertEquals(
            'S000000000000000000000000000001234',
            $result['authority']
        );

        $this->assertEquals(
            'https://www.zarinpal.com/pg/StartPay/S000000000000000000000000000001234',
            $result['payment_url']
        );

        Http::assertSent(function ($request) use ($order) {
            $data = $request->data();

            return
                $request->url() ===
                    'https://api.zarinpal.com/pg/v4/payment/request.json'
                &&
                $request->method() === 'POST'
                &&
                ($data['merchant_id'] ?? null) === 'TEST-MERCHANT-ID'
                &&
                ($data['amount'] ?? null) === 220000
                &&
                ($data['description'] ?? null) ===
                    'پرداخت سفارش ' . $order->order_number
                &&
                ($data['callback_url'] ?? null) ===
                    'http://127.0.0.1:8000/payment/zarinpal/callback';
        });
    }

    public function test_gateway_verify_returns_reference_number_for_successful_payment(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' =>
                Http::response([
                    'data' => [
                        'code' => 100,
                        'message' => 'Verified',
                        'card_pan' => '603799******1234',
                        'ref_id' => 987654321,
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            220000
        );

        $gateway = new ZarinPalGateway();

        $result = $gateway->verify(
            $payment,
            [
                'Authority' => 'S000000000000000000000000000001234',
                'Status' => 'OK',
            ]
        );

        $this->assertTrue(
            $result['success']
        );

        $this->assertTrue(
            $result['verified']
        );

        $this->assertEquals(
            'S000000000000000000000000000001234',
            $result['authority']
        );

        $this->assertEquals(
            '987654321',
            $result['transaction_id']
        );

        $this->assertEquals(
            '987654321',
            $result['reference_number']
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return
                $request->url() ===
                    'https://api.zarinpal.com/pg/v4/payment/verify.json'
                &&
                $request->method() === 'POST'
                &&
                ($data['merchant_id'] ?? null) === 'TEST-MERCHANT-ID'
                &&
                ($data['amount'] ?? null) === 220000
                &&
                ($data['authority'] ?? null) ===
                    'S000000000000000000000000000001234';
        });
    }

    public function test_gateway_does_not_call_verify_when_customer_did_not_complete_payment(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            220000
        );

        $gateway = new ZarinPalGateway();

        $result = $gateway->verify(
            $payment,
            [
                'Authority' => 'S000000000000000000000000000001234',
                'Status' => 'NOK',
            ]
        );

        $this->assertFalse(
            $result['success']
        );

        $this->assertFalse(
            $result['verified']
        );

        $this->assertEquals(
            'S000000000000000000000000000001234',
            $result['authority']
        );

        Http::assertNothingSent();
    }

    public function test_gateway_treats_already_verified_response_as_successful(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' =>
                Http::response([
                    'data' => [
                        'code' => 101,
                        'message' => 'Already verified',
                        'ref_id' => 987654321,
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            220000
        );

        $gateway = new ZarinPalGateway();

        $result = $gateway->verify(
            $payment,
            [
                'Authority' => 'S000000000000000000000000000001234',
                'Status' => 'OK',
            ]
        );

        $this->assertTrue(
            $result['success']
        );

        $this->assertTrue(
            $result['verified']
        );

        $this->assertEquals(
            '987654321',
            $result['reference_number']
        );
    }

    public function test_gateway_rejects_unsuccessful_request_response(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' =>
                Http::response([
                    'data' => [
                        'code' => -9,
                        'message' => 'Invalid merchant',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            220000
        );

        $gateway = new ZarinPalGateway();

        $this->expectException(\RuntimeException::class);

        $gateway->request($payment);
    }
}