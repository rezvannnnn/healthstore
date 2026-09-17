<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(string $name, string $slug, string $sku): Product
    {
        return Product::create([
            'brand_id' => null, 'category_id' => null, 'name' => $name, 'slug' => $slug,
            'sku' => $sku, 'product_type' => 'physical', 'unit' => 'piece', 'quantity_per_unit' => 1,
            'short_description' => null, 'description' => null, 'specifications' => null,
            'expiry_date' => null, 'main_image' => null, 'is_active' => true,
            'is_featured' => false, 'sort_order' => 1,
        ]);
    }

    private function createPrice(Product $product, int $price): ProductPrice
    {
        return ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => $price,
            'compare_at_price' => null, 'min_quantity' => 1, 'is_active' => true,
            'starts_at' => null, 'ends_at' => null,
        ]);
    }

    private function createInventory(Product $product, int $quantity): Warehouse
    {
        $warehouse = Warehouse::create([
            'name' => 'Order Test Warehouse', 'code' => 'ORDER-TEST-WH-'.uniqid(),
            'description' => null, 'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => $quantity,
            'minimum_quantity' => 1, 'batch_number' => 'ORDER-TEST-BATCH-'.uniqid(),
            'expiry_date' => null, 'is_active' => true,
        ]);

        return $warehouse;
    }

    private function createOrderService(CartService $cartService): OrderService
    {
        return new OrderService(new InventoryService, $cartService, new InventoryReservationService);
    }

    public function test_confirmed_cart_can_be_converted_to_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Order Test Product', 'order-test-product', 'ORDER-001');
        $this->createPrice($product, 180000);
        $this->createInventory($product, 10);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 2);
        $this->assertEquals(180000, (float) $item->unit_price);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $order = $this->createOrderService($cartService)->createFromCart($cart);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(360000, (float) $order->subtotal);
        $this->assertEquals(360000, (float) $order->total_amount);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertNotEmpty($order->order_number);
        $this->assertCount(1, $order->items);
        $orderItem = $order->items->first();
        $this->assertEquals($product->id, $orderItem->product_id);
        $this->assertEquals('Order Test Product', $orderItem->product_name);
        $this->assertEquals('ORDER-001', $orderItem->product_sku);
        $this->assertEquals(2, $orderItem->quantity);
        $this->assertEquals(180000, (float) $orderItem->unit_price);
        $this->assertEquals(360000, (float) $orderItem->total_amount);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_order_preserves_product_snapshot_information(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Snapshot Product', 'snapshot-product', 'SNAP-001');
        $this->createPrice($product, 250000);
        $this->createInventory($product, 5);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $order = $this->createOrderService($cartService)->createFromCart($cart);
        $product->update(['name' => 'Changed Product Name', 'sku' => 'SNAP-999']);
        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertEquals('Snapshot Product', $orderItem->product_name);
        $this->assertEquals('SNAP-001', $orderItem->product_sku);
        $this->assertEquals(250000, (float) $orderItem->unit_price);
    }

    public function test_order_creation_reserves_inventory_without_consuming_physical_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Inventory Order Product', 'inventory-order-product', 'INV-ORDER-001');
        $this->createPrice($product, 100000);
        $warehouse = $this->createInventory($product, 10);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 3);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $order = $this->createOrderService($cartService)->createFromCart($cart);
        $inventory = Inventory::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
        $this->assertEquals(10, $inventory->quantity);
        $reservation = InventoryReservation::where('order_id', $order->id)->where('product_id', $product->id)->where('status', 'active')->first();
        $this->assertNotNull($reservation);
        $this->assertEquals(3, $reservation->quantity);
        $this->assertEquals(7, (new InventoryService)->getAvailableQuantity($product));
    }

    public function test_order_creation_marks_cart_as_converted(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Converted Cart Product', 'converted-cart-product', 'CART-ORDER-001');
        $this->createPrice($product, 120000);
        $this->createInventory($product, 10);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $this->createOrderService($cartService)->createFromCart($cart);
        $cart->refresh();
        $this->assertEquals('converted', $cart->status);
    }

    public function test_order_creation_fails_when_inventory_is_insufficient(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Insufficient Inventory Product', 'insufficient-inventory-product', 'INV-ORDER-002');
        $this->createPrice($product, 100000);
        $this->createInventory($product, 1);
        $cart = Cart::create(['user_id' => $user->id, 'status' => 'active', 'coupon_id' => null, 'coupon_code' => null, 'discount_amount' => 0]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100000, 'total_price' => 200000]);
        $this->expectException(RuntimeException::class);
        $this->createOrderService(new CartService)->createFromCart($cart);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_creation_uses_inventory_reservation_service(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct('Delegated Reservation Product', 'delegated-reservation-product', 'DELEGATE-001');
        $this->createPrice($product, 150000);
        $warehouse = $this->createInventory($product, 10);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 3);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $order = $this->createOrderService($cartService)->createFromCart($cart);
        $reservations = InventoryReservation::where('order_id', $order->id)->where('product_id', $product->id)->where('status', 'active')->get();
        $this->assertCount(1, $reservations);
        $this->assertEquals(3, $reservations->sum('quantity'));
        $inventory = Inventory::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
        $this->assertNotNull($inventory);
        $this->assertEquals(10, $inventory->quantity);
        $this->assertEquals(7, (new InventoryService)->getAvailableQuantity($product));
    }

    public function test_order_applies_configured_shipping_fee(): void
    {
        StoreSetting::setValue('shipping_fee', '50000');
        StoreSetting::setValue('free_shipping_threshold', '1000000');

        $user = User::factory()->create();
        $product = $this->createProduct('Shipping Product', 'shipping-product', 'SHIP-001');
        $this->createPrice($product, 300000);
        $this->createInventory($product, 5);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();

        $order = $this->createOrderService($cartService)->createFromCart($cart);

        $this->assertEquals(300000, (float) $order->subtotal);
        $this->assertEquals(50000, (float) $order->shipping_amount);
        $this->assertEquals(350000, (float) $order->total_amount);
    }

    public function test_order_uses_free_shipping_at_configured_threshold(): void
    {
        StoreSetting::setValue('shipping_fee', '50000');
        StoreSetting::setValue('free_shipping_threshold', '300000');

        $user = User::factory()->create();
        $product = $this->createProduct('Free Shipping Product', 'free-shipping-product', 'SHIP-002');
        $this->createPrice($product, 300000);
        $this->createInventory($product, 5);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();

        $order = $this->createOrderService($cartService)->createFromCart($cart);

        $this->assertEquals(0, (float) $order->shipping_amount);
        $this->assertEquals(300000, (float) $order->total_amount);
    }

    public function test_order_rejects_subtotal_below_configured_minimum(): void
    {
        StoreSetting::setValue('min_order_amount', '500000');

        $user = User::factory()->create();
        $product = $this->createProduct('Minimum Order Product', 'minimum-order-product', 'MIN-001');
        $this->createPrice($product, 300000);
        $this->createInventory($product, 5);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('حداقل مبلغ سفارش');
        $this->createOrderService($cartService)->createFromCart($cart);
        $this->assertDatabaseCount('orders', 0);
    }
}
