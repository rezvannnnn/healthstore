<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Cancellation Test Product',
            'slug' => 'cancellation-test-product-' . uniqid(),
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
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Cancellation Test Warehouse',
            'code' => 'CANCEL-WH-' . uniqid(),
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createInventory(
        Product $product,
        Warehouse $warehouse,
        int $quantity
    ): Inventory {
        return Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'minimum_quantity' => 1,
            'batch_number' => 'CANCEL-BATCH-' . uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);
    }

    private function createOrder(
        User $user,
        string $status = 'pending',
        string $paymentStatus = 'pending'
    ): Order {
        return Order::create([
            'order_number' => 'ORD-CANCEL-' . now()->format('YmdHis') . '-' . uniqid(),
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

    private function createReservation(
        Order $order,
        Product $product,
        Inventory $inventory,
        int $quantity
    ): InventoryReservation {
        return InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);
    }

    public function test_customer_can_cancel_own_pending_order(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder($user);

        $reservation = $this->createReservation(
            $order,
            $product,
            $inventory,
            3
        );

        $response = $this->actingAs($user)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertRedirect();

        $order->refresh();
        $reservation->refresh();
        $inventory->refresh();

        $this->assertEquals(
            'cancelled',
            $order->status
        );

        $this->assertNotNull(
            $order->cancelled_at
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->released_at
        );

        $this->assertEquals(
            10,
            $inventory->quantity
        );
    }

    public function test_customer_cannot_cancel_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = $this->createOrder($owner);

        $response = $this->actingAs($otherUser)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertStatus(404);

        $order->refresh();

        $this->assertEquals(
            'pending',
            $order->status
        );

        $this->assertNull(
            $order->cancelled_at
        );
    }

    public function test_guest_cannot_cancel_order(): void
    {
    $user = User::factory()->create();

    $order = $this->createOrder($user);

    $response = $this->post(
        '/orders/' . $order->order_number . '/cancel'
    );

    $response->assertStatus(401);

    $order->refresh();

    $this->assertEquals(
        'pending',
        $order->status
    );

    $this->assertNull(
        $order->cancelled_at
    );
    }

    public function test_paid_order_cannot_be_cancelled_by_customer(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            'paid',
            'paid'
        );

        $response = $this->from(
            '/orders/' . $order->order_number
        )->actingAs($user)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'order',
        ]);

        $order->refresh();

        $this->assertEquals(
            'paid',
            $order->status
        );

        $this->assertNull(
            $order->cancelled_at
        );
    }

    public function test_cancelled_order_cannot_be_cancelled_again(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            'cancelled',
            'pending'
        );

        $order->update([
            'cancelled_at' => now(),
        ]);

        $response = $this->from(
            '/orders/' . $order->order_number
        )->actingAs($user)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'order',
        ]);

        $order->refresh();

        $this->assertEquals(
            'cancelled',
            $order->status
        );
    }

    public function test_cancelling_order_releases_all_active_reservations(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();

        $warehouse1 = $this->createWarehouse();
        $warehouse2 = $this->createWarehouse();

        $inventory1 = $this->createInventory(
            $product,
            $warehouse1,
            5
        );

        $inventory2 = $this->createInventory(
            $product,
            $warehouse2,
            5
        );

        $order = $this->createOrder($user);

        $reservation1 = $this->createReservation(
            $order,
            $product,
            $inventory1,
            2
        );

        $reservation2 = $this->createReservation(
            $order,
            $product,
            $inventory2,
            3
        );

        $response = $this->actingAs($user)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertRedirect();

        $reservation1->refresh();
        $reservation2->refresh();

        $this->assertEquals(
            'released',
            $reservation1->status
        );

        $this->assertEquals(
            'released',
            $reservation2->status
        );

        $this->assertNotNull(
            $reservation1->released_at
        );

        $this->assertNotNull(
            $reservation2->released_at
        );
    }

    public function test_cancelling_order_does_not_restore_physical_stock_twice(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder($user);

        $reservation = $this->createReservation(
            $order,
            $product,
            $inventory,
            4
        );

        $service = new InventoryReservationService();

        $this->assertTrue(
            $service->release($reservation)
        );

        $inventory->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $reservation->refresh();

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $response = $this->actingAs($user)->post(
            '/orders/' . $order->order_number . '/cancel'
        );

        $response->assertRedirect();

        $inventory->refresh();
        $reservation->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );
    }
}