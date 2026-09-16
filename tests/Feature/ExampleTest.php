<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private function createAvailableInventory(
        Product $product,
        int $quantity = 10
    ): Warehouse {
        $warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'TEST-WH',
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);

        return $warehouse;
    }

    public function test_product_can_be_added_to_cart_with_current_price(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test Product', 'slug' => 'test-product', 'sku' => 'TEST-001',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 150000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $service = new CartService;
        $item = $service->addItem($user->id, $product->id, 2);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(150000, (float) $item->unit_price);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $this->assertNotNull($cart);
        $this->assertEquals(300000, $service->calculateSubtotal($cart));
    }

    public function test_cart_detects_price_change_before_checkout(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test Product 2', 'slug' => 'test-product-2', 'sku' => 'TEST-002',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 150000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $service = new CartService;
        $item = $service->addItem($user->id, $product->id, 1);
        $this->assertEquals(150000, (float) $item->unit_price);
        ProductPrice::where('product_id', $product->id)->update(['price' => 180000]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $changes = $service->checkPriceChanges($cart);
        $this->assertCount(1, $changes);
        $this->assertEquals('price_changed', $changes[0]['type']);
        $this->assertEquals(150000, $changes[0]['old_price']);
        $this->assertEquals(180000, $changes[0]['new_price']);
    }

    public function test_unchanged_cart_has_no_price_confirmation_required(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test Product 3', 'slug' => 'test-product-3', 'sku' => 'TEST-003',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 200000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $service = new CartService;
        $service->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $changes = $service->checkPriceChanges($cart);
        $this->assertCount(0, $changes);
    }

    public function test_checkout_detects_price_change_and_updates_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Checkout Test Product', 'slug' => 'checkout-test-product', 'sku' => 'CHECKOUT-001',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 150000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 2);
        $this->assertEquals(150000, (float) $item->unit_price);
        ProductPrice::where('product_id', $product->id)->update(['price' => 180000]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $result = $checkoutService->prepare($cart);
        $this->assertTrue($result['requires_price_confirmation']);
        $this->assertCount(1, $result['changes']);
        $this->assertEquals(150000, $result['changes'][0]['old_price']);
        $this->assertEquals(180000, $result['changes'][0]['new_price']);
        $item->refresh();
        $this->assertEquals(180000, (float) $item->unit_price);
        $this->assertEquals(360000, $result['subtotal']);
    }

    public function test_checkout_does_not_require_confirmation_when_price_is_unchanged(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Checkout Stable Product', 'slug' => 'checkout-stable-product', 'sku' => 'CHECKOUT-002',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 250000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $result = $checkoutService->prepare($cart);
        $this->assertFalse($result['requires_price_confirmation']);
        $this->assertCount(0, $result['changes']);
        $this->assertEquals(250000, $result['subtotal']);
    }

    public function test_customer_can_confirm_changed_price_and_continue(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Confirm Price Product', 'slug' => 'confirm-price-product', 'sku' => 'CONFIRM-001',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 150000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $cartService = new CartService;
        $cartService->addItem($user->id, $product->id, 1);
        ProductPrice::where('product_id', $product->id)->update(['price' => 180000]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $checkout = $checkoutService->prepare($cart);
        $this->assertTrue($checkout['requires_price_confirmation']);
        $this->assertEquals(180000, (float) $checkout['cart']->items->first()->unit_price);
        $result = $checkoutService->confirmPriceChanges($cart);
        $this->assertTrue($result['confirmed']);
        $this->assertTrue($result['payment_allowed']);
        $this->assertEquals(180000, $result['subtotal']);
    }

    public function test_customer_can_reject_changed_price_and_keep_updated_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Reject Price Product', 'slug' => 'reject-price-product', 'sku' => 'REJECT-001',
            'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true,
            'is_featured' => false, 'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id, 'price_type' => 'retail', 'price' => 150000,
            'min_quantity' => 1, 'is_active' => true,
        ]);
        $this->createAvailableInventory($product);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 2);
        ProductPrice::where('product_id', $product->id)->update(['price' => 180000]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $checkoutService->prepare($cart);
        $item->refresh();
        $this->assertEquals(180000, (float) $item->unit_price);
        $result = $checkoutService->rejectPriceChanges($cart);
        $this->assertFalse($result['confirmed']);
        $this->assertFalse($result['payment_allowed']);
        $item->refresh();
        $this->assertEquals(180000, (float) $item->unit_price);
    }

    public function test_product_is_available_when_valid_inventory_exists(): void
    {
        $product = Product::create(['name' => 'Available Product', 'slug' => 'available-product', 'sku' => 'INV-001', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5, 'minimum_quantity' => 1, 'is_active' => true]);
        $service = new InventoryService;
        $this->assertTrue($service->isAvailable($product));
        $this->assertEquals(5, $service->getAvailableQuantity($product));
    }

    public function test_product_is_not_available_when_inventory_is_zero(): void
    {
        $product = Product::create(['name' => 'Out Of Stock Product', 'slug' => 'out-of-stock-product', 'sku' => 'INV-002', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 0, 'minimum_quantity' => 1, 'is_active' => true]);
        $service = new InventoryService;
        $this->assertFalse($service->isAvailable($product));
        $this->assertEquals(0, $service->getAvailableQuantity($product));
    }

    public function test_inactive_inventory_is_not_available(): void
    {
        $product = Product::create(['name' => 'Inactive Inventory Product', 'slug' => 'inactive-inventory-product', 'sku' => 'INV-003', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10, 'minimum_quantity' => 1, 'is_active' => false]);
        $service = new InventoryService;
        $this->assertFalse($service->isAvailable($product));
        $this->assertEquals(0, $service->getAvailableQuantity($product));
    }

    public function test_expired_inventory_is_not_available(): void
    {
        $product = Product::create(['name' => 'Expired Product', 'slug' => 'expired-product', 'sku' => 'INV-004', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10, 'minimum_quantity' => 1, 'expiry_date' => now()->subDay(), 'is_active' => true]);
        $service = new InventoryService;
        $this->assertFalse($service->isAvailable($product));
        $this->assertEquals(0, $service->getAvailableQuantity($product));
    }

    public function test_available_quantity_is_combined_from_multiple_warehouses(): void
    {
        $product = Product::create(['name' => 'Multi Warehouse Product', 'slug' => 'multi-warehouse-product', 'sku' => 'INV-005', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        $warehouse1 = Warehouse::create(['name' => 'Warehouse One', 'code' => 'WH-01', 'is_active' => true]);
        $warehouse2 = Warehouse::create(['name' => 'Warehouse Two', 'code' => 'WH-02', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse1->id, 'quantity' => 3, 'minimum_quantity' => 1, 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse2->id, 'quantity' => 7, 'minimum_quantity' => 1, 'is_active' => true]);
        $service = new InventoryService;
        $this->assertTrue($service->isAvailable($product));
        $this->assertEquals(10, $service->getAvailableQuantity($product));
    }

    public function test_checkout_marks_out_of_stock_product_and_sets_price_to_zero(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Out Of Stock Checkout Product', 'slug' => 'out-of-stock-checkout-product', 'sku' => 'OOS-CHECKOUT-001', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        ProductPrice::create(['product_id' => $product->id, 'price_type' => 'retail', 'price' => 200000, 'min_quantity' => 1, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 2, 'minimum_quantity' => 1, 'is_active' => true]);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 1);
        $this->assertEquals(200000, (float) $item->unit_price);
        Inventory::where('product_id', $product->id)->update(['quantity' => 0]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $result = $checkoutService->prepare($cart);
        $item->refresh();
        $this->assertNotNull($item);
        $this->assertEquals(0, (float) $item->unit_price);
        $this->assertTrue($result['requires_price_confirmation']);
        $this->assertEquals(0, $result['subtotal']);
        $this->assertCount(1, $result['availability_changes']);
    }

    public function test_customer_confirmation_removes_out_of_stock_product_from_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Remove Out Of Stock Product', 'slug' => 'remove-out-of-stock-product', 'sku' => 'OOS-CONFIRM-001', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        ProductPrice::create(['product_id' => $product->id, 'price_type' => 'retail', 'price' => 250000, 'min_quantity' => 1, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 1, 'minimum_quantity' => 1, 'is_active' => true]);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 1);
        Inventory::where('product_id', $product->id)->update(['quantity' => 0]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $checkoutService->prepare($cart);
        $item->refresh();
        $this->assertEquals(0, (float) $item->unit_price);
        $result = $checkoutService->confirmPriceChanges($cart);
        $this->assertTrue($result['confirmed']);
        $this->assertFalse($result['payment_allowed']);
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        $this->assertEquals(0, $result['subtotal']);
    }

    public function test_customer_rejection_keeps_out_of_stock_product_in_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Reject Out Of Stock Product', 'slug' => 'reject-out-of-stock-product', 'sku' => 'OOS-REJECT-001', 'product_type' => 'physical', 'unit' => 'piece', 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
        ProductPrice::create(['product_id' => $product->id, 'price_type' => 'retail', 'price' => 300000, 'min_quantity' => 1, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_active' => true]);
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 1, 'minimum_quantity' => 1, 'is_active' => true]);
        $cartService = new CartService;
        $item = $cartService->addItem($user->id, $product->id, 1);
        Inventory::where('product_id', $product->id)->update(['quantity' => 0]);
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        $checkoutService = new CheckoutService($cartService, new InventoryService);
        $checkoutService->prepare($cart);
        $item->refresh();
        $this->assertEquals(0, (float) $item->unit_price);
        $result = $checkoutService->rejectPriceChanges($cart);
        $this->assertFalse($result['confirmed']);
        $this->assertFalse($result['payment_allowed']);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'product_id' => $product->id, 'unit_price' => 0]);
    }
}
