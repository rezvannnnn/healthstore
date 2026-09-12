<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zarinpal.merchant_id' => 'TEST-MERCHANT-ID',
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
            'order_number' => 'ORD-GW-' . now()->format('YmdHis') . '-' . uniqid(),
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

    public function test_payment_service_resolves_configured_gateway(): void
    {
        $gateway = app(
            PaymentGatewayInterface::class
        );

        $this->assertInstanceOf(
            \App\Services\Payment\ZarinPalGateway::class,
            $gateway
        );
    }

    public function test_payment_service_can_request_gateway_payment(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' =>
                Http::response([
                    'data' => [
                        'code' => 100,
                        'message' => 'Success',
                        'authority' => 'S000000000000000000000000000009999',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $service = app(PaymentService::class);

        $payment = $service->create($order);

        $result = $service->requestGatewayPayment(
            $payment
        );

        $payment->refresh();

        $this->assertEquals(
            'zarinpal',
            $payment->gateway
        );

        $this->assertEquals(
            'S000000000000000000000000000009999',
            $payment->authority
        );

        $this->assertEquals(
            'https://www.zarinpal.com/pg/StartPay/S000000000000000000000000000009999',
            $result['payment_url']
        );

        $this->assertNotNull(
            $payment->gateway_response
        );

        $this->assertEquals(
            220000,
            (float) $payment->amount
        );
    }

    public function test_payment_service_does_not_create_second_gateway_request_for_existing_authority(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' =>
                Http::response([
                    'data' => [
                        'code' => 100,
                        'authority' => 'S000000000000000000000000000008888',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $service = app(PaymentService::class);

        $payment = $service->create($order);

        $firstResult = $service->requestGatewayPayment(
            $payment
        );

        $secondResult = $service->requestGatewayPayment(
            $payment
        );

        $this->assertEquals(
            $firstResult['authority'],
            $secondResult['authority']
        );

        $this->assertEquals(
            $firstResult['payment_url'],
            $secondResult['payment_url']
        );

        Http::assertSentCount(1);
    }

    public function test_gateway_payment_request_keeps_payment_pending(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' =>
                Http::response([
                    'data' => [
                        'code' => 100,
                        'authority' => 'S000000000000000000000000000007777',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            180000
        );

        $service = app(PaymentService::class);

        $payment = $service->create($order);

        $service->requestGatewayPayment(
            $payment
        );

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(
            'pending',
            $payment->status
        );

        $this->assertEquals(
            'pending',
            $order->status
        );

        $this->assertEquals(
            'pending',
            $order->payment_status
        );
    }
}