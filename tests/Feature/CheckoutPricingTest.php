<?php

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

it('reports checkout shipping and minimum order state from store pricing rules', function () {
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

    StoreSetting::setValue('shipping_fee', '50_000');
    StoreSetting::setValue('free_shipping_threshold', '1_000_000');
    StoreSetting::setValue('min_order_amount', '600_000');

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

    expect($result['subtotal'])->toBe(500_000.0)
        ->and($result['shipping_amount'])->toBe(50_000.0)
        ->and($result['total_amount'])->toBe(550_000.0)
        ->and($result['can_proceed_to_payment'])->toBeFalse()
        ->and($result['minimum_order_amount'])->toBe(600_000.0)
        ->and($result['minimum_order_met'])->toBeFalse();
});
