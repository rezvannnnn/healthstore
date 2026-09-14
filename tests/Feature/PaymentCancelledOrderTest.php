<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCancelledOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_paid_closes_payment_when_order_was_cancelled(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'cancelled',
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
            'cancelled_at' => now(),
        ]);

        $service = new PaymentService(new InventoryReservationService);
        $payment = $service->create($order->forceFill(['status' => 'pending']));
        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $result = $service->markAsPaid($payment, 'TX-CANCELLED');

        $payment->refresh();
        $this->assertFalse($result);
        $this->assertSame('cancelled', $payment->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }
}
