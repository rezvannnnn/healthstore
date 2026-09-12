<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(
        User $user,
        float $totalAmount = 360000
    ): Order {
        return Order::create([
            'order_number' => 'ORD-PAY-' . now()->format('YmdHis') . '-' . uniqid(),
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

    public function test_payment_can_be_created_for_an_order(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            360000
        );

        $service = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $service->create($order);

        $this->assertInstanceOf(
            Payment::class,
            $payment
        );

        $this->assertEquals(
            $order->id,
            $payment->order_id
        );

        $this->assertEquals(
            360000,
            (float) $payment->amount
        );

        $this->assertEquals(
            'pending',
            $payment->status
        );

        $this->assertNull(
            $payment->gateway
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_payment_uses_exact_order_total(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            123456
        );

        $service = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $service->create($order);

        $this->assertEquals(
            (float) $order->total_amount,
            (float) $payment->amount
        );
    }

    public function test_zero_value_order_cannot_create_payment(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            0
        );

        $service = new PaymentService(
            new InventoryReservationService()
        );

        $this->expectException(RuntimeException::class);

        $service->create($order);
    }

    public function test_payment_cannot_be_created_for_cancelled_order(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            360000
        );

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $service = new PaymentService(
            new InventoryReservationService()
        );

        $this->expectException(RuntimeException::class);

        $service->create($order);
    }

    public function test_duplicate_pending_payment_is_not_created(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            360000
        );

        $service = new PaymentService(
            new InventoryReservationService()
        );

        $firstPayment = $service->create($order);

        $secondPayment = $service->create($order);

        $this->assertEquals(
            $firstPayment->id,
            $secondPayment->id
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );
    }

    public function test_successful_payment_marks_payment_as_paid_order_as_paid_and_consumes_reservations(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            360000
        );

        $product = Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Payment Product',
            'slug' => 'payment-product-' . uniqid(),
            'sku' => 'PAY-' . uniqid(),
            'product_type' => 'physical',
            'unit' => 'piece',
            'quantity_per_unit' => 1,
            'short_description' => null,
            'description' => null,
            'specifications' => null,
            'expiry_date' => null,
            'main_image' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Payment Warehouse',
            'code' => 'PAY-WH-' . uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'minimum_quantity' => 1,
            'batch_number' => 'PAY-BATCH-' . uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $paymentService = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $paymentService->create($order);

        $result = $paymentService->markAsPaid(
            $payment,
            'TX-123456'
        );

        $payment->refresh();
        $order->refresh();
        $reservation->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'paid',
            $payment->status
        );

        $this->assertEquals(
            'TX-123456',
            $payment->transaction_id
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertEquals(
            'paid',
            $order->payment_status
        );

        $this->assertNotNull(
            $order->paid_at
        );

        $this->assertEquals(
            'paid',
            $order->status
        );

        $this->assertEquals(
            'consumed',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->consumed_at
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'transaction_id' => 'TX-123456',
        ]);
    }

    public function test_failed_payment_marks_payment_as_failed_and_releases_reservations(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            200000
        );

        $product = Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Failed Payment Product',
            'slug' => 'failed-payment-product-' . uniqid(),
            'sku' => 'FAIL-' . uniqid(),
            'product_type' => 'physical',
            'unit' => 'piece',
            'quantity_per_unit' => 1,
            'short_description' => null,
            'description' => null,
            'specifications' => null,
            'expiry_date' => null,
            'main_image' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Failed Payment Warehouse',
            'code' => 'FAIL-WH-' . uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 7,
            'minimum_quantity' => 1,
            'batch_number' => 'FAIL-BATCH-' . uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        /*
         * Simulate the physical stock being held by the reservation.
         */
        $inventory->decrement(
            'quantity',
            3
        );

        $inventory->refresh();

        $this->assertEquals(
            4,
            $inventory->quantity
        );

        $paymentService = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $paymentService->create($order);

        $result = $paymentService->markAsFailed(
            $payment,
            'Gateway payment failed'
        );

        $payment->refresh();
        $order->refresh();
        $reservation->refresh();
        $inventory->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'failed',
            $payment->status
        );

        $this->assertNotNull(
            $payment->gateway_response
        );

        $this->assertEquals(
            'pending',
            $order->payment_status
        );

        $this->assertEquals(
            'pending',
            $order->status
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->released_at
        );

        /*
         * Releasing the reservation does not change physical stock.
         */
        $this->assertEquals(
            4,
            $inventory->quantity
        );
    }

    public function test_cancelled_payment_releases_reservations(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            150000
        );

        $product = Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Cancelled Payment Product',
            'slug' => 'cancelled-payment-product-' . uniqid(),
            'sku' => 'CANCEL-' . uniqid(),
            'product_type' => 'physical',
            'unit' => 'piece',
            'quantity_per_unit' => 1,
            'short_description' => null,
            'description' => null,
            'specifications' => null,
            'expiry_date' => null,
            'main_image' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Cancelled Payment Warehouse',
            'code' => 'CANCEL-WH-' . uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'minimum_quantity' => 1,
            'batch_number' => 'CANCEL-BATCH-' . uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $inventory->decrement(
            'quantity',
            2
        );

        $paymentService = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $paymentService->create($order);

        $result = $paymentService->cancel(
            $payment
        );

        $payment->refresh();
        $reservation->refresh();
        $inventory->refresh();

        $this->assertTrue($result);

        $this->assertEquals(
            'cancelled',
            $payment->status
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertEquals(
            3,
            $inventory->quantity
        );
    }

    public function test_paid_payment_cannot_be_marked_as_failed_or_paid_again(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            100000
        );

        $paymentService = new PaymentService(
            new InventoryReservationService()
        );

        $payment = $paymentService->create($order);

        $this->assertTrue(
            $paymentService->markAsPaid(
                $payment,
                'TX-999999'
            )
        );

        $payment->refresh();

        $this->assertFalse(
            $paymentService->markAsFailed(
                $payment,
                'Should not change'
            )
        );

        $payment->refresh();

        $this->assertFalse(
            $paymentService->markAsPaid(
                $payment,
                'TX-000000'
            )
        );

        $payment->refresh();

        $this->assertEquals(
            'paid',
            $payment->status
        );

        $this->assertEquals(
            'TX-999999',
            $payment->transaction_id
        );
    }
}

