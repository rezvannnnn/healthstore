<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerErrorExtraTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_failure_does_not_change_order_or_payment_to_paid(): void
    {
        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['code' => -9, 'message' => 'Invalid merchant'],
                'errors' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-PERR-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100000,
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

        $response = $this->from('/orders/'.$order->order_number)
            ->actingAs($user)
            ->post('/orders/'.$order->order_number.'/payment');

        $response->assertRedirect('/orders/'.$order->order_number);
        $response->assertSessionHas('error');
        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_cancelled_order_payment_request_does_not_call_gateway(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-PCANCEL-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100000,
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
            'cancelled_at' => now(),
        ]);

        $response = $this->from('/orders/'.$order->order_number)
            ->actingAs($user)
            ->post('/orders/'.$order->order_number.'/payment');

        $response->assertRedirect('/orders/'.$order->order_number);
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }
}
