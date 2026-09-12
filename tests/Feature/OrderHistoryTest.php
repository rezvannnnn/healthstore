<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(
        string $phone
    ): User {
        return User::factory()->create([
            'name' => 'Test Customer',
            'email' => null,
            'phone' => $phone,
            'phone_verified_at' => now(),
            'password' => null,
        ]);
    }

    private function createOrder(
        User $user,
        string $orderNumber,
        string $status = 'pending',
        string $paymentStatus = 'pending',
        int $totalAmount = 100000
    ): Order {
        return Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => 'IRR',
            'recipient_name' => 'Test Customer',
            'recipient_phone' => $user->phone,
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'shipping_address' => 'Test address',
            'postal_code' => '1234567890',
            'customer_note' => null,
            'admin_note' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    private function createProduct(
        string $name,
        string $sku
    ): Product {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => $name,
            'slug' => strtolower(
                str_replace(
                    ' ',
                    '-',
                    $name
                )
            ) . '-' . uniqid(),
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

    public function test_guest_cannot_view_order_history(): void
    {
        $response = $this->get(
            '/account/orders'
        );

        $response->assertRedirect();
    }

    public function test_authenticated_customer_can_view_own_order_history(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $order1 = $this->createOrder(
            $user,
            'ORD-HISTORY-001',
            'paid',
            'paid',
            150000
        );

        $order2 = $this->createOrder(
            $user,
            'ORD-HISTORY-002',
            'delivered',
            'paid',
            250000
        );

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $order1,
            $order2
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 2)
                ->where(
                    'orders.0.id',
                    $order2->id
                )
                ->where(
                    'orders.0.order_number',
                    $order2->order_number
                )
                ->where(
                    'orders.0.status',
                    'delivered'
                )
                ->where(
                    'orders.0.total_amount',
                    '250000.00'
                )
                ->where(
                    'orders.1.id',
                    $order1->id
                )
                ->where(
                    'orders.1.order_number',
                    $order1->order_number
                )
                ->where(
                    'orders.1.status',
                    'paid'
                )
                ->where(
                    'orders.1.total_amount',
                    '150000.00'
                );
        });
    }

    public function test_customer_cannot_see_another_customers_orders(): void
    {
        $owner = $this->createUser(
            '09121111111'
        );

        $otherUser = $this->createUser(
            '09122222222'
        );

        $ownerOrder = $this->createOrder(
            $owner,
            'ORD-HISTORY-OWNER',
            'paid',
            'paid',
            200000
        );

        $otherOrder = $this->createOrder(
            $otherUser,
            'ORD-HISTORY-OTHER',
            'paid',
            'paid',
            300000
        );

        $response = $this->actingAs($otherUser)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $owner,
            $ownerOrder,
            $otherOrder
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 1)
                ->where(
                    'orders.0.id',
                    $otherOrder->id
                )
                ->where(
                    'orders.0.order_number',
                    $otherOrder->order_number
                )
                ->where(
                    'orders.0.user_id',
                    $otherOrder->user_id
                );

            $page->missing(
                'orders.1'
            );

            $this->assertDatabaseHas(
                'orders',
                [
                    'id' => $ownerOrder->id,
                    'user_id' => $owner->id,
                ]
            );
        });
    }

    public function test_order_history_is_sorted_with_newest_order_first(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $oldOrder = $this->createOrder(
            $user,
            'ORD-HISTORY-OLD',
            'paid',
            'paid',
            100000
        );

        $oldOrder->created_at = now()->subDays(2);
        $oldOrder->save();

        $newOrder = $this->createOrder(
            $user,
            'ORD-HISTORY-NEW',
            'paid',
            'paid',
            200000
        );

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $newOrder,
            $oldOrder
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 2)
                ->where(
                    'orders.0.id',
                    $newOrder->id
                )
                ->where(
                    'orders.1.id',
                    $oldOrder->id
                );
        });
    }

    public function test_order_history_contains_basic_order_information(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $order = $this->createOrder(
            $user,
            'ORD-HISTORY-BASIC',
            'processing',
            'paid',
            450000
        );

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $order
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 1)
                ->where(
                    'orders.0.id',
                    $order->id
                )
                ->where(
                    'orders.0.order_number',
                    $order->order_number
                )
                ->where(
                    'orders.0.status',
                    'processing'
                )
                ->where(
                    'orders.0.payment_status',
                    'paid'
                )
                ->where(
                    'orders.0.total_amount',
                    '450000.00'
                )
                ->where(
                    'orders.0.currency',
                    'IRR'
                );
        });
    }

    public function test_order_history_does_not_expose_other_customers_orders_even_when_they_are_more_recent(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $otherUser = $this->createUser(
            '09129876543'
        );

        $userOldOrder = $this->createOrder(
            $user,
            'ORD-HISTORY-MINE',
            'pending',
            'pending',
            100000
        );

        $userOldOrder->created_at = now()->subDays(3);
        $userOldOrder->save();

        $otherRecentOrder = $this->createOrder(
            $otherUser,
            'ORD-HISTORY-FOREIGN',
            'paid',
            'paid',
            900000
        );

        $otherRecentOrder->created_at = now();
        $otherRecentOrder->save();

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $userOldOrder
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 1)
                ->where(
                    'orders.0.id',
                    $userOldOrder->id
                )
                ->where(
                    'orders.0.order_number',
                    $userOldOrder->order_number
                );
        });
    }

    public function test_order_history_can_include_orders_without_items(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $order = $this->createOrder(
            $user,
            'ORD-HISTORY-NO-ITEMS',
            'cancelled',
            'pending',
            0
        );

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $order
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 1)
                ->where(
                    'orders.0.id',
                    $order->id
                )
                ->where(
                    'orders.0.status',
                    'cancelled'
                );
        });
    }

    public function test_order_history_does_not_need_product_details_for_the_list(): void
    {
        $user = $this->createUser(
            '09121234567'
        );

        $order = $this->createOrder(
            $user,
            'ORD-HISTORY-PRODUCT',
            'paid',
            'paid',
            200000
        );

        $product = $this->createProduct(
            'History Test Product',
            'HISTORY-001'
        );

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'total_amount' => 200000,
        ]);

        $response = $this->actingAs($user)->get(
            '/account/orders'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use (
            $order
        ) {
            $page
                ->component('Account/Orders/Index')
                ->has('orders', 1)
                ->where(
                    'orders.0.id',
                    $order->id
                )
                ->where(
                    'orders.0.order_number',
                    $order->order_number
                );
        });
    }
}