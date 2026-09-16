<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(bool $active = true): Product
    {
        $product = Product::create([
            'name' => 'Cart Product '.uniqid(),
            'slug' => 'cart-product-'.uniqid(),
            'sku' => 'CART-'.uniqid(),
            'is_active' => $active,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 100000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_customer_can_view_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/cart');

        $response->assertOk();
    }

    public function test_customer_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $response = $this->actingAs($user)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100000,
        ]);
    }

    public function test_customer_gets_quantity_based_price_when_adding_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 85000,
            'min_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 85000,
        ]);
    }

    public function test_customer_can_update_own_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
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

        $response = $this->actingAs($user)->put("/cart/{$cart->id}/items/{$item->id}", [
            'quantity' => 3,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 3,
            'unit_price' => 100000,
        ]);
    }

    public function test_customer_gets_quantity_based_price_when_updating_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 85000,
            'min_quantity' => 5,
            'is_active' => true,
        ]);

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

        $response = $this->actingAs($user)->put("/cart/{$cart->id}/items/{$item->id}", [
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
            'unit_price' => 85000,
        ]);
    }

    public function test_customer_cannot_update_another_customers_cart(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->createProduct();
        $cart = Cart::create([
            'user_id' => $owner->id,
            'status' => 'active',
        ]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $response = $this->actingAs($other)->put("/cart/{$cart->id}/items/{$item->id}", [
            'quantity' => 3,
        ]);

        $response->assertNotFound();
    }

    public function test_customer_can_remove_own_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
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

        $response = $this->actingAs($user)->delete("/cart/{$cart->id}/items/{$item->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_customer_cannot_update_inactive_product_in_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(false);
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

        $this->actingAs($user)
            ->put("/cart/{$cart->id}/items/{$item->id}", ['quantity' => 2])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_add_is_blocked_while_customer_cart_lock_is_held(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $lock = Cache::lock("cart:user:{$user->id}", 30);
        $this->assertTrue($lock->get());

        try {
            $this->actingAs($user)
                ->post('/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ])
                ->assertSessionHasErrors('product_id');

            $this->assertDatabaseMissing('cart_items', [
                'product_id' => $product->id,
            ]);
        } finally {
            $lock->release();
        }
    }
}
