<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-VERIFY-'.now()->format('YmdHis').'-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
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

    public function test_verification_does_not_call_gateway_for_already_paid_payment(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 220000,
            'gateway' => 'zarinpal',
            'status' => 'paid',
            'authority' => 'AUTH-PAID',
            'transaction_id' => 'TX-PAID',
            'reference_number' => null,
            'card_last_four' => null,
            'card_token' => null,
            'gateway_response' => null,
            'paid_at' => now(),
            'refunded_at' => null,
        ]);

        $gateway = new class implements PaymentGatewayInterface
        {
            public int $verifyCalls = 0;

            public function request(Payment $payment): array
            {
                return [];
            }

            public function verify(Payment $payment, array $callbackData): array
            {
                $this->verifyCalls++;

                return [
                    'success' => true,
                    'verified' => true,
                    'transaction_id' => 'TX-SHOULD-NOT-BE-USED',
                    'reference_number' => 'REF-SHOULD-NOT-BE-USED',
                    'response' => ['code' => 100],
                ];
            }

            public function paymentUrl(array $gatewayData): string
            {
                return 'https://gateway.test/pay/'.($gatewayData['authority'] ?? '');
            }
        };

        $service = new PaymentService(new InventoryReservationService, $gateway);
        $result = $service->verifyGatewayPayment($payment, [
            'Authority' => 'AUTH-PAID',
            'Status' => 'OK',
        ]);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertSame('paid', $result['payment']->status);
        $this->assertSame(0, $gateway->verifyCalls);
    }

    public function test_pending_payment_is_verified_and_gateway_response_is_stored(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 220000,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => 'AUTH-PENDING',
            'transaction_id' => null,
            'reference_number' => null,
            'card_last_four' => null,
            'card_token' => null,
            'gateway_response' => null,
            'paid_at' => null,
            'refunded_at' => null,
        ]);

        $gateway = new class implements PaymentGatewayInterface
        {
            public int $verifyCalls = 0;

            public function request(Payment $payment): array
            {
                return [];
            }

            public function verify(Payment $payment, array $callbackData): array
            {
                $this->verifyCalls++;

                return [
                    'success' => true,
                    'verified' => true,
                    'transaction_id' => 'TX-VERIFY-123',
                    'reference_number' => 'REF-VERIFY-123',
                    'response' => ['code' => 100, 'message' => 'verified'],
                ];
            }

            public function paymentUrl(array $gatewayData): string
            {
                return 'https://gateway.test/pay/'.($gatewayData['authority'] ?? '');
            }
        };

        $service = new PaymentService(new InventoryReservationService, $gateway);
        $result = $service->verifyGatewayPayment($payment, [
            'Authority' => 'AUTH-PENDING',
            'Status' => 'OK',
        ]);

        $payment->refresh();

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertSame('TX-VERIFY-123', $result['transaction_id']);
        $this->assertSame('REF-VERIFY-123', $result['reference_number']);
        $this->assertSame(1, $gateway->verifyCalls);
        $this->assertSame(
            ['code' => 100, 'message' => 'verified'],
            json_decode($payment->gateway_response, true, 512, JSON_THROW_ON_ERROR)
        );
        $this->assertSame('pending', $payment->status);
    }
}
