<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\StorePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_checkout_shipping_and_minimum_order_state_from_store_pricing_rules(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Pricing Test Product',
            'slug' => 'pricing-test-product',
            'sku' => 'PRICING-001',
            'product_type' => 'physical',
            'unit' => 'piece',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 500_000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Pricing Test Warehouse',
            'code' => 'PRICING-WH',
            'is_active' => true,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'minimum_quantity' => 1,
            'is_active' => true,
        ]);

        StoreSetting::setValue('shipping_fee', '50000');
        StoreSetting::setValue('free_shipping_threshold', '1000000');
        StoreSetting::setValue('min_order_amount', '600000');

        $cart = Cart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 500_000,
        ]);

        $service = new CheckoutService(
            app(CartService::class),
            app(InventoryService::class),
            app(StorePricingService::class),
        );

        $result = $service->prepare($cart);

        $this->assertSame(500_000.0, $result['subtotal']);
        $this->assertSame(50_000.0, $result['shipping_amount']);
        $this->assertSame(550_000.0, $result['total_amount']);
        $this->assertFalse($result['can_proceed_to_payment']);
        $this->assertSame(600_000.0, $result['minimum_order_amount']);
        $this->assertFalse($result['minimum_order_met']);
    }
}
