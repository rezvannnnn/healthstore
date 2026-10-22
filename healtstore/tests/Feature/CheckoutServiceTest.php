<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'name' => 'Checkout Tier Product',
            'slug' => 'checkout-tier-product',
            'sku' => 'CHECKOUT-TIER-001',
            'is_active' => true,
        ]);
    }

    private function createInventory(Product $product, int $quantity = 20): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Checkout Test Warehouse',
            'code' => 'CHECKOUT-TEST-WH-'.uniqid(),
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'minimum_quantity' => 1,
            'batch_number' => 'CHECKOUT-TEST-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);
    }

    private function createTieredPrices(Product $product): void
    {
        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 100000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 80000,
            'min_quantity' => 10,
            'is_active' => true,
        ]);
    }

    private function createCheckoutService(): CheckoutService
    {
        return new CheckoutService(
            new CartService,
            new InventoryService
        );
    }

    public function test_prepare_uses_cart_item_quantity_for_tiered_price(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 80000,
        ]);

        $result = $this->createCheckoutService()->prepare($cart);

        $this->assertSame([], $result['price_changes']);
        $this->assertFalse($result['requires_price_confirmation']);
        $this->assertEquals(80000, (float) $item->fresh()->unit_price);
        $this->assertEquals(800000, $result['subtotal']);
    }

    public function test_prepare_detects_tiered_price_change_for_cart_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100000,
        ]);

        $result = $this->createCheckoutService()->prepare($cart);

        $this->assertCount(1, $result['price_changes']);
        $this->assertSame('price_changed', $result['price_changes'][0]['type']);
        $this->assertEquals(100000, $result['price_changes'][0]['old_price']);
        $this->assertEquals(80000, $result['price_changes'][0]['new_price']);
        $this->assertTrue($result['requires_price_confirmation']);
        $this->assertEquals(800000, $result['subtotal']);
        $this->assertEquals(80000, (float) $item->fresh()->unit_price);
    }

    public function test_confirm_uses_cart_item_quantity_for_tiered_price(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 80000,
        ]);

        $result = $this->createCheckoutService()->confirmPriceChanges($cart);

        $this->assertTrue($result['confirmed']);
        $this->assertTrue($result['payment_allowed']);
        $this->assertEquals(800000, $result['subtotal']);
    }

    public function test_confirm_rejects_price_change_after_cart_quantity_changes(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 80000,
        ]);

        $item->update([
            'quantity' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('قیمت سبد خرید دوباره تغییر کرده است');

        $this->createCheckoutService()->confirmPriceChanges($cart);
    }

    public function test_prepare_marks_out_of_stock_item_without_removing_it(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product, 0);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $result = $this->createCheckoutService()->prepare($cart);

        $this->assertCount(1, $result['availability_changes']);
        $this->assertSame('out_of_stock', $result['availability_changes'][0]['type']);
        $this->assertEquals(100000, $result['availability_changes'][0]['old_price']);
        $this->assertEquals(0, $result['availability_changes'][0]['new_price']);
        $this->assertTrue($result['requires_price_confirmation']);
        $this->assertTrue($item->fresh()->exists);
        $this->assertEquals(0, (float) $item->fresh()->unit_price);
        $this->assertEquals(0, $result['subtotal']);
        $this->assertFalse($result['can_proceed_to_payment']);
    }

    public function test_confirm_removes_out_of_stock_items_only_after_confirmation(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product, 0);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 0,
        ]);

        $result = $this->createCheckoutService()->confirmPriceChanges($cart);

        $this->assertTrue($result['confirmed']);
        $this->assertFalse($result['payment_allowed']);
        $this->assertEquals(0, $result['subtotal']);
        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_reject_price_changes_keeps_out_of_stock_item_in_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->createTieredPrices($product);
        $this->createInventory($product, 0);

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $this->createCheckoutService()->prepare($cart);

        $result = $this->createCheckoutService()->rejectPriceChanges($cart);

        $this->assertFalse($result['confirmed']);
        $this->assertFalse($result['payment_allowed']);
        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
        ]);
    }
}
