<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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

    private function createOrder(
        User $user,
        int $totalAmount = 220000
    ): Order {
        return Order::create([
            'order_number' => 'ORD-PCTRL-'.now()->format('YmdHis').'-'.uniqid(),
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

    private function fakeSuccessfulZarinPalRequest(
        string $authority
    ): void {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'message' => 'Success',
                    'authority' => $authority,
                ],
                'errors' => [],
            ], 200),
        ]);
    }

    public function test_authenticated_customer_can_start_payment_for_own_order(): void
    {
        $authority = 'S000000000000000000000000000001111';

        $this->fakeSuccessfulZarinPalRequest(
            $authority
        );

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $response = $this->actingAs($user)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $response->assertRedirect(
            'https://www.zarinpal.com/pg/StartPay/'.$authority
        );

        $payment = Payment::where(
            'order_id',
            $order->id
        )->first();

        $this->assertNotNull($payment);

        $this->assertEquals(
            220000,
            (float) $payment->amount
        );

        $this->assertEquals(
            'pending',
            $payment->status
        );

        $this->assertEquals(
            'zarinpal',
            $payment->gateway
        );

        $this->assertEquals(
            $authority,
            $payment->authority
        );
    }

    public function test_customer_cannot_start_payment_for_another_customers_order(): void
    {
        Http::fake();

        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $order = $this->createOrder(
            $owner,
            220000
        );

        $response = $this->actingAs($otherUser)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $response->assertStatus(404);

        $this->assertDatabaseCount(
            'payments',
            0
        );

        Http::assertNothingSent();
    }

    public function test_repeating_payment_request_does_not_create_duplicate_pending_payment(): void
    {
        $authority = 'S000000000000000000000000000002222';

        $this->fakeSuccessfulZarinPalRequest(
            $authority
        );

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $firstResponse = $this->actingAs($user)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $firstResponse->assertRedirect(
            'https://www.zarinpal.com/pg/StartPay/'.$authority
        );

        $secondResponse = $this->actingAs($user)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $secondResponse->assertRedirect(
            'https://www.zarinpal.com/pg/StartPay/'.$authority
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );

        $payment = Payment::where(
            'order_id',
            $order->id
        )->first();

        $this->assertNotNull($payment);

        $this->assertEquals(
            'pending',
            $payment->status
        );

        $this->assertEquals(
            220000,
            (float) $payment->amount
        );

        $this->assertEquals(
            $authority,
            $payment->authority
        );

        Http::assertSentCount(1);
    }

    public function test_payment_service_is_responsible_for_creating_the_payment(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            150000
        );

        $paymentService = new PaymentService(
            new InventoryReservationService
        );

        $payment = $paymentService->create($order);

        $this->assertInstanceOf(
            Payment::class,
            $payment
        );

        $this->assertEquals(
            $order->id,
            $payment->order_id
        );

        $this->assertEquals(
            150000,
            (float) $payment->amount
        );

        $this->assertEquals(
            'pending',
            $payment->status
        );
    }

    public function test_authenticated_customer_can_start_gateway_payment_for_own_order(): void
    {
        $authority = 'S000000000000000000000000000006666';

        $this->fakeSuccessfulZarinPalRequest(
            $authority
        );

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $response = $this->actingAs($user)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $response->assertRedirect(
            'https://www.zarinpal.com/pg/StartPay/'.$authority
        );

        $payment = Payment::where(
            'order_id',
            $order->id
        )->first();

        $this->assertNotNull($payment);

        $this->assertEquals(
            'pending',
            $payment->status
        );

        $this->assertEquals(
            'zarinpal',
            $payment->gateway
        );

        $this->assertEquals(
            $authority,
            $payment->authority
        );
    }
}
