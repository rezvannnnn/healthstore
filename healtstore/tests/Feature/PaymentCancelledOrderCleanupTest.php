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
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCancelledOrderCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_marked_paid_after_order_cancellation_releases_inventory_reservation(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-CLEANUP-'.uniqid(),
            'user_id' => $user->id,
            'customer_type' => 'b2c',
            'business_profile_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100000,
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

        $product = Product::create([
            'brand_id' => null, 'category_id' => null, 'name' => 'Cancelled Cleanup Product',
            'slug' => 'cancelled-cleanup-product-'.uniqid(), 'sku' => 'CLEANUP-'.uniqid(),
            'product_type' => 'physical', 'unit' => 'piece', 'quantity_per_unit' => 1,
            'short_description' => null, 'description' => null, 'specifications' => null,
            'expiry_date' => null, 'main_image' => null, 'is_active' => true,
            'is_featured' => false, 'sort_order' => 1,
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Cancelled Cleanup Warehouse', 'code' => 'CLEANUP-WH-'.uniqid(),
            'description' => null, 'is_active' => true,
        ]);
        $inventory = Inventory::create([
            'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5,
            'minimum_quantity' => 1, 'batch_number' => 'CLEANUP-BATCH-'.uniqid(),
            'expiry_date' => null, 'is_active' => true,
        ]);
        $reservation = InventoryReservation::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'inventory_id' => $inventory->id,
            'quantity' => 2, 'status' => 'active', 'expires_at' => now()->addMinutes(20),
            'released_at' => null, 'consumed_at' => null,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id, 'amount' => 100000, 'gateway' => null,
            'status' => 'pending', 'authority' => null, 'transaction_id' => null,
            'reference_number' => null, 'card_last_four' => null, 'card_token' => null,
            'gateway_response' => null, 'paid_at' => null, 'refunded_at' => null,
        ]);
        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $service = new PaymentService(new InventoryReservationService);
        $result = $service->markAsPaid($payment, 'TX-LATE');

        $reservation->refresh();
        $payment->refresh();

        $this->assertFalse($result);
        $this->assertSame('released', $reservation->status);
        $this->assertNotNull($reservation->released_at);
        $this->assertSame('cancelled', $payment->status);
        $this->assertSame('TX-LATE', $payment->transaction_id);
    }
}
