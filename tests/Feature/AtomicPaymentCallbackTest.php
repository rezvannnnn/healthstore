<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtomicPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user): Order
    {
        return Order::create([
            'order_number' => 'ORD-ATOMIC-'.now()->format('YmdHis').'-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 220000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 220000,
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

    public function test_callback_finalization_is_idempotent_after_first_success(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $payment = $order->payments()->create([
            'amount' => 220000,
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'authority' => 'AUTH-ATOMIC',
            'transaction_id' => null,
            'reference_number' => null,
            'card_last_four' => null,
            'card_token' => null,
            'gateway_response' => null,
            'paid_at' => null,
            'refunded_at' => null,
        ]);

        $product = Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Atomic Callback Product',
            'slug' => 'atomic-callback-'.uniqid(),
            'sku' => 'ATOMIC-'.uniqid(),
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
            'name' => 'Atomic Warehouse',
            'code' => 'ATOMIC-WH-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'minimum_quantity' => 1,
            'batch_number' => 'ATOMIC-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $reservation = InventoryReservation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'status' => 'active',
            'expires_at' => now()->addMinutes(20),
            'released_at' => null,
            'consumed_at' => null,
        ]);

        $inventory->decrement('quantity', 2);

        $gateway = new class implements PaymentGatewayInterface
        {
            public int $verifyCalls = 0;

            public function request(Payment $payment): array
            {
                return [];
            }

            public function verify(Payment $payment, array $callbackData): array
            {
                $this->verifyCalls++;

                return [
                    'success' => true,
                    'verified' => true,
                    'transaction_id' => 'TX-ATOMIC-123',
                    'reference_number' => 'REF-ATOMIC-123',
                    'response' => ['code' => 100, 'ref_id' => 123],
                ];
            }

            public function paymentUrl(array $gatewayData): string
            {
                return 'https://gateway.test/pay/'.($gatewayData['authority'] ?? '');
            }
        };

        $service = new PaymentService(new InventoryReservationService, $gateway);

        $first = $service->verifyAndFinalizeGatewayPayment($payment, [
            'Authority' => 'AUTH-ATOMIC',
            'Status' => 'OK',
        ]);

        $payment->refresh();
        $order->refresh();
        $reservation->refresh();
        $inventory->refresh();

        $this->assertSame('paid', $first['status']);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('consumed', $reservation->status);
        $this->assertSame(8, $inventory->quantity);
        $this->assertSame('TX-ATOMIC-123', $payment->transaction_id);

        $second = $service->verifyAndFinalizeGatewayPayment($payment, [
            'Authority' => 'AUTH-ATOMIC',
            'Status' => 'OK',
        ]);

        $payment->refresh();
        $reservation->refresh();
        $inventory->refresh();

        $this->assertSame('already_paid', $second['status']);
        $this->assertSame(1, $gateway->verifyCalls);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('consumed', $reservation->status);
        $this->assertSame(8, $inventory->quantity);
    }
}
