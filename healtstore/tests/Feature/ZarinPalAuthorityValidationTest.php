<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\ZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZarinPalAuthorityValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zarinpal.merchant_id' => 'TEST-MERCHANT-ID',
            'services.zarinpal.verify_endpoint' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        ]);
    }

    public function test_gateway_rejects_callback_authority_that_does_not_match_payment(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-AUTH-'.uniqid(),
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

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 220000,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => 'EXPECTED-AUTHORITY',
        ]);

        $result = (new ZarinPalGateway)->verify($payment, [
            'Authority' => 'ATTACKER-AUTHORITY',
            'Status' => 'OK',
        ]);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertSame('ATTACKER-AUTHORITY', $result['authority']);
        Http::assertNothingSent();
    }
}
