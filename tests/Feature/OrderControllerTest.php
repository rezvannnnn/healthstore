<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
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

    private function createInventory(
        Product $product,
        int $quantity
    ): Warehouse {
        $warehouse = Warehouse::create([
            'name' => 'Controller Test Warehouse',
            'code' => 'CTRL-WH-' . uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'minimum_quantity' => 1,
            'batch_number' => 'CTRL-BATCH-' . uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        return $warehouse;
    }

    private function createOrder(
        User $user,
        int $totalAmount = 240000
    ): Order {
        return Order::create([
            'order_number' => 'ORD-CTRL-' . now()->format('YmdHis') . '-' . uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => $totalAmount,
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

    public function test_authenticated_customer_can_view_own_order(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            240000
        );

        $product = $this->createProduct(
            'Controller Order Product',
            'controller-order-product',
            'CTRL-ORDER-001'
        );

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 120000,
            'discount_amount' => 0,
            'total_amount' => 240000,
        ]);

        $response = $this->actingAs($user)->get(
            '/orders/' . $order->order_number
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use ($order) {
            $page
                ->component('Order/Show')
                ->where('order.id', $order->id)
                ->where('order.order_number', $order->order_number)
                ->where('order.total_amount', '240000.00')
                ->has('order.items', 1);
        });
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = $this->createOrder(
            $owner,
            180000
        );

        $response = $this->actingAs($otherUser)->get(
            '/orders/' . $order->order_number
        );

        $response->assertStatus(404);
    }

    public function test_customer_can_confirm_checkout_and_create_order(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct(
            'Checkout Integration Product',
            'checkout-integration-product',
            'CHECKOUT-CTRL-001'
        );

        $this->createPrice(
            $product,
            120000
        );

        $this->createInventory(
            $product,
            10
        );

        $cartService = new CartService();

        $cartService->addItem(
            $user->id,
            $product->id,
            2
        );

        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        /*
         * Simulate the final Checkout validation.
         *
         * CheckoutController performs this step before
         * calling OrderService.
         */
        app(\App\Services\CheckoutService::class)
            ->prepare($cart);

        $response = $this->actingAs($user)->post(
            '/checkout/confirm'
        );

        $response->assertRedirect();

        $order = Order::where(
            'user_id',
            $user->id
        )->first();

        $this->assertNotNull($order);

        $this->assertEquals(
            240000,
            (float) $order->total_amount
        );

        $this->assertEquals(
            'pending',
            $order->status
        );

        $this->assertEquals(
            'pending',
            $order->payment_status
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 120000,
            'total_amount' => 240000,
        ]);

        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 'active',
        ]);
    }
}