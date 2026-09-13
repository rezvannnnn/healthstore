<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_percent_coupon_respects_max_discount(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'SAVE20',
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 100000,
            'max_discount_amount' => 30000,
            'is_active' => true,
        ]);

        $result = app(CouponService::class)->prepareForOrder(
            'save20',
            $user->id,
            200000
        );

        $this->assertSame($coupon->id, $result['coupon']?->id);
        $this->assertSame(30000.0, $result['discount_amount']);
    }

    public function test_fixed_coupon_and_minimum_order_are_enforced(): void
    {
        $user = User::factory()->create();
        Coupon::create([
            'code' => 'FIXED50',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 300000,
            'is_active' => true,
        ]);

        $this->expectExceptionMessage('مبلغ سفارش برای استفاده از این کد تخفیف کافی نیست.');

        app(CouponService::class)->prepareForOrder(
            'FIXED50',
            $user->id,
            200000
        );
    }

    public function test_coupon_dates_and_active_flag_are_enforced(): void
    {
        $user = User::factory()->create();

        Coupon::create([
            'code' => 'FUTURE10',
            'type' => 'percent',
            'value' => 10,
            'starts_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->expectExceptionMessage('زمان استفاده از این کد تخفیف هنوز نرسیده است.');
        app(CouponService::class)->prepareForOrder('FUTURE10', $user->id, 200000);
    }

    public function test_per_user_usage_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create([
            'code' => 'USER10',
            'type' => 'percent',
            'value' => 10,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'COUPON-LIMIT-001',
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 200000,
            'discount_amount' => 20000,
            'shipping_amount' => 0,
            'total_amount' => 180000,
            'currency' => 'IRR',
            'coupon_id' => $coupon->id,
            'coupon_code' => 'USER10',
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'consumed',
            'reserved_at' => now()->subHour(),
            'consumed_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('شما قبلاً به سقف استفاده از این کد تخفیف رسیده‌اید.');

        app(CouponService::class)->prepareForOrder(
            'USER10',
            $user->id,
            200000
        );
    }

    public function test_empty_coupon_code_is_a_no_op(): void
    {
        $user = User::factory()->create();

        $result = app(CouponService::class)->prepareForOrder(
            null,
            $user->id,
            200000
        );

        $this->assertNull($result['coupon']);
        $this->assertSame(0.0, $result['discount_amount']);
    }
}
