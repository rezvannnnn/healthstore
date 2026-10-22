<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReservationTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(
        string $name,
        string $slug,
        string $sku
    ): Product {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
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

    private function createPrice(
        Product $product,
        int $price
    ): ProductPrice {
        return ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => $price,
            'compare_at_price' => null,
            'min_quantity' => 1,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Reservation Test Warehouse',
            'code' => 'RES-TEST-WH-'.uniqid(),
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
            'batch_number' => 'RES-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);
    }

    private function createOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-RES-'.now()->format('YmdHis').'-'.uniqid(),
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
    }

    public function test_inventory_reservation_can_reduce_available_stock(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Reservation Product',
            'reservation-product',
            'RES-001'
        );

        $this->createPrice(
            $product,
            100000
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder(
            $user
        );

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

        $inventory->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $this->assertEquals(
            'active',
            $reservation->status
        );

        $service = new InventoryService;

        $this->assertEquals(
            7,
            $service->getAvailableQuantity($product)
        );

        $this->assertDatabaseHas(
            'inventory_reservations',
            [
                'id' => $reservation->id,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'inventory_id' => $inventory->id,
                'quantity' => 3,
                'status' => 'active',
            ]
        );
    }

    public function test_active_reservation_has_an_expiration_time(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Expiring Reservation Product',
            'expiring-reservation-product',
            'RES-002'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            5
        );

        $order = $this->createOrder(
            $user
        );

        $expiresAt = now()->addMinutes(20);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $this->assertNotNull(
            $reservation->expires_at
        );

        $this->assertEquals(
            'active',
            $reservation->status
        );

        $this->assertTrue(
            $reservation->expires_at->greaterThan(now())
        );
    }

    public function test_reservation_can_be_released_without_changing_physical_stock(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Release Reservation Product',
            'release-reservation-product',
            'RES-003'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder(
            $user
        );

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 4,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $inventory->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $service = new InventoryReservationService;

        $result = $service->release(
            $reservation
        );

        $inventory->refresh();
        $reservation->refresh();

        $this->assertTrue(
            $result
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

        $inventoryService = new InventoryService;

        $this->assertEquals(
            10,
            $inventoryService->getAvailableQuantity($product)
        );
    }

    public function test_successful_payment_consumes_reservation_and_reduces_physical_stock(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Consumed Reservation Product',
            'consumed-reservation-product',
            'RES-004'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder(
            $user
        );

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

        $service = new InventoryReservationService;

        $result = $service->consume(
            $reservation
        );

        $reservation->refresh();
        $inventory->refresh();

        $this->assertTrue(
            $result
        );

        $this->assertEquals(
            'consumed',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->consumed_at
        );

        $this->assertNull(
            $reservation->released_at
        );

        $this->assertEquals(
            7,
            $inventory->quantity
        );

        $inventoryService = new InventoryService;

        $this->assertEquals(
            7,
            $inventoryService->getAvailableQuantity($product)
        );
    }

    public function test_consumed_reservation_cannot_be_released_again(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Consumed Then Released Product',
            'consumed-then-released-product',
            'RES-007'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder(
            $user
        );

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

        $service = new InventoryReservationService;

        $this->assertTrue(
            $service->consume($reservation)
        );

        $this->assertFalse(
            $service->release($reservation)
        );

        $reservation->refresh();

        $this->assertEquals(
            'consumed',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->consumed_at
        );

        $this->assertNull(
            $reservation->released_at
        );
    }

    public function test_expired_reservation_can_be_released_without_changing_physical_stock(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Expired Reservation Product',
            'expired-reservation-product',
            'RES-005'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            8
        );

        $order = $this->createOrder(
            $user
        );

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 5,
            'status' => 'active',
            'expires_at' => now()->subMinute(),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $inventory->refresh();

        $this->assertEquals(
            8,
            $inventory->quantity
        );

        $service = new InventoryReservationService;

        $releasedCount = $service->releaseExpired();

        $reservation->refresh();
        $inventory->refresh();

        $this->assertEquals(
            1,
            $releasedCount
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->released_at
        );

        $this->assertEquals(
            8,
            $inventory->quantity
        );

        $inventoryService = new InventoryService;

        $this->assertEquals(
            8,
            $inventoryService->getAvailableQuantity($product)
        );
    }

    public function test_available_quantity_excludes_active_reservations(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Reserved Stock Product',
            'reserved-stock-product',
            'RES-006'
        );

        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            10
        );

        $order = $this->createOrder(
            $user
        );

        InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $service = new InventoryService;

        $this->assertEquals(
            7,
            $service->getAvailableQuantity($product)
        );

        $this->assertTrue(
            $service->isAvailable($product)
        );
    }
}
