<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider nonPendingOrderStatuses */
    public function test_payment_cannot_be_created_for_a_non_pending_order(string $status): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-STATUS-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => $status,
            'payment_status' => $status === 'paid' ? 'paid' : 'pending',
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
            'paid_at' => $status === 'paid' ? now() : null,
            'shipped_at' => $status === 'shipped' ? now() : null,
            'delivered_at' => $status === 'delivered' ? now() : null,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);

        $service = new PaymentService(new InventoryReservationService);

        $this->expectException(RuntimeException::class);
        $service->create($order);
    }

    public static function nonPendingOrderStatuses(): array
    {
        return [
            'paid' => ['paid'],
            'processing' => ['processing'],
            'shipped' => ['shipped'],
            'delivered' => ['delivered'],
            'cancelled' => ['cancelled'],
        ];
    }
}
