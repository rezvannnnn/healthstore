<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);
    }

    private function createOrder(
        User $user,
        string $status = 'pending',
        string $paymentStatus = 'pending'
    ): Order {
        return Order::create([
            'order_number' => 'ORD-STATUS-' . now()->format('YmdHis') . '-' . uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => $status,
            'payment_status' => $paymentStatus,
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
    }

    public function test_pending_order_cannot_directly_become_processing(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'pending',
            'pending'
        );

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'processing'
        );
    }

    public function test_pending_order_cannot_be_marked_as_cancelled_through_set_status(): void
{
    $user = $this->createUser();

    $order = $this->createOrder(
        $user,
        'pending',
        'pending'
    );

    $this->expectException(RuntimeException::class);

    app(OrderService::class)->setStatus(
        $order,
        'cancelled'
    );
}

    public function test_paid_order_can_be_moved_to_processing(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'paid',
            'paid'
        );

        $order->update([
            'paid_at' => now(),
            'confirmed_at' => now(),
        ]);

        $result = app(OrderService::class)->setStatus(
            $order,
            'processing'
        );

        $order->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'processing',
            $order->status
        );

        $this->assertNull(
            $order->shipped_at
        );
    }

    public function test_processing_order_can_be_shipped(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'processing',
            'paid'
        );

        $order->update([
            'paid_at' => now(),
            'confirmed_at' => now(),
        ]);

        $result = app(OrderService::class)->setStatus(
            $order,
            'shipped'
        );

        $order->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'shipped',
            $order->status
        );

        $this->assertNotNull(
            $order->shipped_at
        );

        $this->assertNull(
            $order->delivered_at
        );
    }

    public function test_shipped_order_can_be_delivered(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'shipped',
            'paid'
        );

        $order->update([
            'paid_at' => now(),
            'confirmed_at' => now(),
            'shipped_at' => now(),
        ]);

        $result = app(OrderService::class)->setStatus(
            $order,
            'delivered'
        );

        $order->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'delivered',
            $order->status
        );

        $this->assertNotNull(
            $order->shipped_at
        );

        $this->assertNotNull(
            $order->delivered_at
        );
    }

    public function test_paid_order_cannot_go_back_to_pending(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'paid',
            'paid'
        );

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'pending'
        );
    }

    public function test_processing_order_cannot_go_back_to_paid(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'processing',
            'paid'
        );

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'paid'
        );
    }

    public function test_delivered_order_cannot_be_shipped_again(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'delivered',
            'paid'
        );

        $order->update([
            'paid_at' => now(),
            'confirmed_at' => now(),
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'shipped'
        );
    }

    public function test_cancelled_order_cannot_become_processing(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'cancelled',
            'pending'
        );

        $order->update([
            'cancelled_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'processing'
        );
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $user = $this->createUser();

        $order = $this->createOrder(
            $user,
            'delivered',
            'paid'
        );

        $order->update([
            'paid_at' => now(),
            'confirmed_at' => now(),
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        app(OrderService::class)->setStatus(
            $order,
            'cancelled'
        );
    }
}