<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InventoryReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Reservation Service Product',
            'slug' => 'reservation-service-product-'.uniqid(),
            'sku' => 'RS-'.uniqid(),
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
            'name' => 'Reservation Service Warehouse',
            'code' => 'RS-WH-'.uniqid(),
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
            'batch_number' => 'RS-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);
    }

    private function createOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-RS-'.now()->format('YmdHis').'-'.uniqid(),
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

    private function createActiveReservation(
        Order $order,
        Product $product,
        Inventory $inventory,
        int $quantity,
        int $minutes = 20
    ): InventoryReservation {
        return InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
            'status' => 'active',
            'expires_at' => now()->addMinutes($minutes),
            'released_at' => null,
            'consumed_at' => null,
        ]);
    }

    public function test_active_reservation_can_be_consumed_and_physical_stock_is_reduced(): void
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

        $reservation = $this->createActiveReservation(
            $order,
            $product,
            $inventory,
            3
        );

        $service = new InventoryReservationService;

        $result = $service->consume($reservation);

        $reservation->refresh();
        $inventory->refresh();

        $this->assertTrue($result);

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
    }

    public function test_active_reservation_can_be_released_without_changing_physical_stock(): void
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

        $reservation = $this->createActiveReservation(
            $order,
            $product,
            $inventory,
            4
        );

        $inventory->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $service = new InventoryReservationService;

        $result = $service->release($reservation);

        $reservation->refresh();
        $inventory->refresh();

        $this->assertTrue($result);

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

    public function test_expired_active_reservations_can_be_released_without_changing_physical_stock(): void
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

        $reservation = $this->createActiveReservation(
            $order,
            $product,
            $inventory,
            5,
            -1
        );

        $inventory->refresh();

        $this->assertEquals(
            10,
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
            10,
            $inventory->quantity
        );
    }

    public function test_consumed_reservation_cannot_be_released_again(): void
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

        $reservation = $this->createActiveReservation(
            $order,
            $product,
            $inventory,
            3
        );

        $service = new InventoryReservationService;

        $this->assertTrue(
            $service->consume($reservation)
        );

        $reservation->refresh();

        $this->assertEquals(
            'consumed',
            $reservation->status
        );

        $this->assertFalse(
            $service->release($reservation)
        );

        $reservation->refresh();

        $this->assertEquals(
            'consumed',
            $reservation->status
        );
    }

    public function test_released_reservation_cannot_be_consumed_again(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $inventory = $this->createInventory(
            $product,
            $warehouse,
            3
        );

        $order = $this->createOrder($user);

        $reservation = $this->createActiveReservation(
            $order,
            $product,
            $inventory,
            2
        );

        $service = new InventoryReservationService;

        $this->assertTrue(
            $service->release($reservation)
        );

        $reservation->refresh();

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertFalse(
            $service->consume($reservation)
        );

        $reservation->refresh();

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $inventory->refresh();

        $this->assertEquals(
            3,
            $inventory->quantity
        );
    }

    public function test_reserve_can_reserve_quantity_from_one_inventory_record(): void
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

        $service = new InventoryReservationService;

        $reservations = $service->reserve(
            $order,
            $product,
            4
        );

        $this->assertCount(
            1,
            $reservations
        );

        $reservation = $reservations->first();

        $this->assertEquals(
            $order->id,
            $reservation->order_id
        );

        $this->assertEquals(
            $product->id,
            $reservation->product_id
        );

        $this->assertEquals(
            $inventory->id,
            $reservation->inventory_id
        );

        $this->assertEquals(
            4,
            $reservation->quantity
        );

        $this->assertEquals(
            'active',
            $reservation->status
        );

        $this->assertTrue(
            $reservation->expires_at->greaterThan(now())
        );

        $inventory->refresh();

        $this->assertEquals(
            10,
            $inventory->quantity
        );

        $inventoryService = new InventoryService;

        $this->assertEquals(
            6,
            $inventoryService->getAvailableQuantity($product)
        );
    }

    public function test_reserve_can_split_quantity_across_multiple_inventory_records(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();

        $warehouse1 = $this->createWarehouse();
        $warehouse2 = $this->createWarehouse();

        $inventory1 = $this->createInventory(
            $product,
            $warehouse1,
            2
        );

        $inventory2 = $this->createInventory(
            $product,
            $warehouse2,
            5
        );

        $order = $this->createOrder($user);

        $service = new InventoryReservationService;

        $reservations = $service->reserve(
            $order,
            $product,
            6
        );

        $this->assertCount(
            2,
            $reservations
        );

        $this->assertEquals(
            6,
            $reservations->sum('quantity')
        );

        $firstReservation = $reservations
            ->where('inventory_id', $inventory1->id)
            ->first();

        $secondReservation = $reservations
            ->where('inventory_id', $inventory2->id)
            ->first();

        $this->assertNotNull(
            $firstReservation
        );

        $this->assertNotNull(
            $secondReservation
        );

        $this->assertEquals(
            2,
            $firstReservation->quantity
        );

        $this->assertEquals(
            4,
            $secondReservation->quantity
        );

        $inventory1->refresh();
        $inventory2->refresh();

        $this->assertEquals(
            2,
            $inventory1->quantity
        );

        $this->assertEquals(
            5,
            $inventory2->quantity
        );

        $inventoryService = new InventoryService;

        $this->assertEquals(
            1,
            $inventoryService->getAvailableQuantity($product)
        );
    }

    public function test_reserve_fails_without_creating_partial_reservations_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct();

        $warehouse1 = $this->createWarehouse();
        $warehouse2 = $this->createWarehouse();

        $this->createInventory(
            $product,
            $warehouse1,
            2
        );

        $this->createInventory(
            $product,
            $warehouse2,
            3
        );

        $order = $this->createOrder($user);

        $service = new InventoryReservationService;

        try {
            $service->reserve(
                $order,
                $product,
                6
            );

            $this->fail(
                'Expected InventoryReservationService::reserve() to throw RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'موجودی محصول',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'inventory_reservations',
            0
        );
    }
}
