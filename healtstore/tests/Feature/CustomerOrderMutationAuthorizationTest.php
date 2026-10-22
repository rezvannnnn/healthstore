<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerOrderMutationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-AUTH-'.now()->format('YmdHis').'-'.uniqid(),
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

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($owner);

        $response = $this->actingAs($otherUser)->post(
            '/orders/'.$order->order_number.'/cancel'
        );

        $response->assertStatus(404);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $owner->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_customer_cannot_start_payment_for_another_customers_order(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($owner);

        $response = $this->actingAs($otherUser)->post(
            '/orders/'.$order->order_number.'/payment'
        );

        $response->assertStatus(404);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }
}
