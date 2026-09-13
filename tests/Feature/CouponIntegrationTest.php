<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CouponIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => 'Coupon Product',
            'slug' => 'coupon-product',
            'sku' => 'COUPON-001',
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

    private function prepareCart(User $user, int $quantity = 2): Cart
    {
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 100000,
            'compare_at_price' => null,
            'min_quantity' => 1,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Coupon Warehouse',
            'code' => 'COUPON-WH-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
            'minimum_quantity' => 1,
            'batch_number' => 'COUPON-BATCH-'.uniqid(),
            'expiry_date' => null,
            'is_active' => true,
        ]);

        (new CartService)->addItem($user->id, $product->id, $quantity);

        return Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function orderService(): OrderService
    {
        return new OrderService(
            new InventoryService,
            new CartService,
            new InventoryReservationService
        );
    }

    public function test_coupon_is_applied_and_snapshotted_on_order(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 100000,
            'max_discount_amount' => 50000,
            'is_active' => true,
        ]);

        $cart = $this->prepareCart($user);
        $order = $this->orderService()->createFromCart($cart, null, 'save10');

        $this->assertSame(200000.0, (float) $order->subtotal);
        $this->assertSame(20000.0, (float) $order->discount_amount);
        $this->assertSame(180000.0, (float) $order->total_amount);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame('SAVE10', $order->coupon_code);

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'reserved',
        ]);
    }

    public function test_failed_payment_releases_coupon_without_consuming_it(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'FAIL10',
            'type' => 'fixed',
            'value' => 10000,
            'is_active' => true,
        ]);

        $order = $this->orderService()->createFromCart(
            $this->prepareCart($user),
            null,
            'FAIL10'
        );

        $paymentService = new PaymentService(new InventoryReservationService);
        $payment = $paymentService->create($order);

        $this->assertTrue($paymentService->markAsFailed($payment));
        $this->assertSame(0, $coupon->fresh()->used_count);

        $this->assertDatabaseHas('coupon_usages', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
    }

    public function test_successful_payment_consumes_coupon_exactly_once(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'PAID10',
            'type' => 'fixed',
            'value' => 10000,
            'is_active' => true,
        ]);

        $order = $this->orderService()->createFromCart(
            $this->prepareCart($user),
            null,
            'PAID10'
        );

        $paymentService = new PaymentService(new InventoryReservationService);
        $payment = $paymentService->create($order);

        $this->assertTrue($paymentService->markAsPaid($payment, 'TX-COUPON-001'));
        $this->assertFalse($paymentService->markAsPaid($payment->fresh(), 'TX-COUPON-002'));

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('coupon_usages', [
            'order_id' => $order->id,
            'status' => 'consumed',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'transaction_id' => 'TX-COUPON-001',
        ]);
    }

    public function test_order_cancellation_releases_coupon_reservation(): void
    {
        $user = User::factory()->create();
        Coupon::create([
            'code' => 'CANCEL10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $order = $this->orderService()->createFromCart(
            $this->prepareCart($user),
            null,
            'CANCEL10'
        );

        $this->assertTrue($this->orderService()->cancel($order));

        $this->assertDatabaseHas('coupon_usages', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
    }

    public function test_coupon_usage_limit_counts_reserved_usage(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'LIMIT10',
            'type' => 'fixed',
            'value' => 10000,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $order = $this->orderService()->createFromCart(
            $this->prepareCart($user),
            null,
            'LIMIT10'
        );

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'status' => 'reserved',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ظرفیت استفاده از این کد تخفیف تکمیل شده است.');

        app(CouponService::class)->prepareForOrder(
            'LIMIT10',
            $otherUser->id,
            200000
        );
    }
}
