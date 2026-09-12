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

        $service = new CheckoutService(
            new CartService,
            new InventoryService
        );

        $result = $service->prepare($cart);

        $this->assertSame([], $result['price_changes']);
        $this->assertFalse($result['requires_price_confirmation']);
        $this->assertEquals(80000, (float) $item->fresh()->unit_price);
        $this->assertEquals(800000, $result['subtotal']);
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

        $service = new CheckoutService(
            new CartService,
            new InventoryService
        );

        $result = $service->confirmPriceChanges($cart);

        $this->assertTrue($result['confirmed']);
        $this->assertTrue($result['payment_allowed']);
        $this->assertEquals(800000, $result['subtotal']);
    }
}
