<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZarinPalCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.zarinpal.merchant_id' => 'TEST-MERCHANT-ID',
            'services.zarinpal.verify_endpoint' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
            'services.zarinpal.payment_base_url' => 'https://www.zarinpal.com',
            'services.zarinpal.callback_url' => 'http://127.0.0.1:8000/payment/zarinpal/callback',
        ]);
    }

    private function createOrder(
        User $user,
        int $totalAmount = 220000
    ): Order {
        return Order::create([
            'order_number' => 'ORD-CB-'.now()->format('YmdHis').'-'.uniqid(),
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

    private function createPayment(
        Order $order,
        string $authority = 'S000000000000000000000000000001111',
        int $amount = 220000
    ): Payment {
        return $order->payments()->create([
            'amount' => $amount,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => $authority,
            'transaction_id' => null,
            'reference_number' => null,
            'card_last_four' => null,
            'card_token' => null,
            'gateway_response' => null,
            'paid_at' => null,
            'refunded_at' => null,
        ]);
    }

    private function createInventoryReservation(
        Order $order,
        int $quantity = 1
    ): InventoryReservation {
        $product = Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Callback Test Product',
            'slug' => 'callback-test-product-'.uniqid(),
            'sku' => 'CALLBACK-'.uniqid(),
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

        $warehouse = Warehouse::create([
            'name' => 'Callback Test Warehouse',
            'code' => 'CALLBACK-WH-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'minimum_quantity' => 1,
            'batch_number' => 'CALLBACK-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        /*
         * Simulate stock already being held by the reservation.
         */
        $inventory->decrement(
            'quantity',
            $quantity
        );

        return $reservation;
    }

    public function test_successful_callback_verifies_payment_and_marks_order_as_paid(): void
    {
        $authority = 'S000000000000000000000000000001111';

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'message' => 'Verified',
                    'ref_id' => 987654321,
                ],
                'errors' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            220000
        );

        $payment = $this->createPayment(
            $order,
            $authority,
            220000
        );

        $reservation = $this->createInventoryReservation(
            $order,
            1
        );

        $response = $this->get(
            '/payment/zarinpal/callback?'.
            http_build_query([
                'Authority' => $authority,
                'Status' => 'OK',
            ])
        );

        $response->assertRedirect(
            '/orders/'.$order->order_number
        );

        $payment->refresh();
        $order->refresh();
        $reservation->refresh();

        $this->assertEquals(
            'paid',
            $payment->status
        );

        $this->assertEquals(
            '987654321',
            $payment->transaction_id
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertEquals(
            'paid',
            $order->status
        );

        $this->assertEquals(
            'paid',
            $order->payment_status
        );

        $this->assertNotNull(
            $order->paid_at
        );

        $this->assertEquals(
            'consumed',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->consumed_at
        );

        Http::assertSent(function ($request) use ($authority) {
            $data = $request->data();

            return
                $request->url() ===
                    'https://api.zarinpal.com/pg/v4/payment/verify.json'
                &&
                $request->method() === 'POST'
                &&
                ($data['merchant_id'] ?? null) ===
                    'TEST-MERCHANT-ID'
                &&
                ($data['amount'] ?? null) === 220000
                &&
                ($data['authority'] ?? null) === $authority;
        });
    }

    public function test_failed_callback_marks_payment_as_failed_and_releases_reservation(): void
    {
        $authority = 'S000000000000000000000000000002222';

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => -22,
                    'message' => 'Transaction not found',
                ],
                'errors' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            200000
        );

        $payment = $this->createPayment(
            $order,
            $authority,
            200000
        );

        $reservation = $this->createInventoryReservation(
            $order,
            2
        );

        $response = $this->get(
            '/payment/zarinpal/callback?'.
            http_build_query([
                'Authority' => $authority,
                'Status' => 'OK',
            ])
        );

        $response->assertRedirect(
            '/orders/'.$order->order_number
        );

        $payment->refresh();
        $order->refresh();
        $reservation->refresh();

        $this->assertEquals(
            'failed',
            $payment->status
        );

        $this->assertEquals(
            'pending',
            $order->status
        );

        $this->assertEquals(
            'pending',
            $order->payment_status
        );

        $this->assertEquals(
            'released',
            $reservation->status
        );

        $this->assertNotNull(
            $reservation->released_at
        );
    }

    public function test_cancelled_gateway_callback_does_not_call_verify(): void
    {
        Http::fake();

        $authority = 'S000000000000000000000000000003333';

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            180000
        );

        $payment = $this->createPayment(
            $order,
            $authority,
            180000
        );

        $response = $this->get(
            '/payment/zarinpal/callback?'.
            http_build_query([
                'Authority' => $authority,
                'Status' => 'NOK',
            ])
        );

        $response->assertRedirect(
            '/orders/'.$order->order_number
        );

        $payment->refresh();
        $order->refresh();

        /*
         * Our callback controller calls markAsFailed() when
         * verification is not successful.
         */
        $this->assertEquals(
            'failed',
            $payment->status
        );

        $this->assertEquals(
            'pending',
            $order->status
        );

        Http::assertNothingSent();
    }

    public function test_already_paid_payment_is_not_processed_again(): void
    {
        Http::fake();

        $authority = 'S000000000000000000000000000004444';

        $user = User::factory()->create();

        $order = $this->createOrder(
            $user,
            150000
        );

        $payment = $this->createPayment(
            $order,
            $authority,
            150000
        );

        $payment->update([
            'status' => 'paid',
            'transaction_id' => '987654321',
            'paid_at' => now(),
        ]);

        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->get(
            '/payment/zarinpal/callback?'.
            http_build_query([
                'Authority' => $authority,
                'Status' => 'OK',
            ])
        );

        $response->assertRedirect(
            '/orders/'.$order->order_number
        );

        Http::assertNothingSent();

        $payment->refresh();

        $this->assertEquals(
            'paid',
            $payment->status
        );

        $this->assertEquals(
            '987654321',
            $payment->transaction_id
        );
    }

    public function test_unknown_authority_does_not_call_gateway(): void
    {
        Http::fake();

        $response = $this->get(
            '/payment/zarinpal/callback?'.
            http_build_query([
                'Authority' => 'S000000000000000000000000000009999',
                'Status' => 'OK',
            ])
        );

        $response->assertRedirect('/checkout');

        Http::assertNothingSent();
    }
}
