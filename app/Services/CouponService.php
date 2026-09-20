<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CouponService
{
    private const RESERVATION_MINUTES = 20;

    /**
     * Validate a coupon and calculate its discount while the coupon row is locked.
     * The caller should invoke this inside the order transaction.
     *
     * @return array{coupon: Coupon|null, discount_amount: float}
     */
    public function prepareForOrder(?string $code, int $userId, float $subtotal): array
    {
        $normalizedCode = strtoupper(trim((string) $code));

        if ($normalizedCode === '') {
            return [
                'coupon' => null,
                'discount_amount' => 0.0,
            ];
        }

        $coupon = Coupon::query()
            ->where('code', $normalizedCode)
            ->lockForUpdate()
            ->first();

        if (! $coupon) {
            throw new RuntimeException('کد تخفیف معتبر نیست.');
        }

        $this->ensureValid($coupon, $subtotal);

        $globalUsage = CouponUsage::query()
            ->where('coupon_id', $coupon->id)
            ->where(function ($query) {
                $query->where('status', 'consumed')
                    ->orWhere(function ($query) {
                        $query->where('status', 'reserved')
                            ->where(function ($query) {
                                $query->where('expires_at', '>', now())
                                    ->orWhere(function ($query) {
                                        $query->whereNull('expires_at')
                                            ->where(
                                                'reserved_at',
                                                '>',
                                                now()->subMinutes(self::RESERVATION_MINUTES)
                                            );
                                    });
                            });
                    });
            })
            ->count();

        if ($coupon->usage_limit !== null && $globalUsage >= (int) $coupon->usage_limit) {
            throw new RuntimeException('ظرفیت استفاده از این کد تخفیف تکمیل شده است.');
        }

        $userUsage = CouponUsage::query()
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', 'consumed')
                    ->orWhere(function ($query) {
                        $query->where('status', 'reserved')
                            ->where(function ($query) {
                                $query->where('expires_at', '>', now())
                                    ->orWhere(function ($query) {
                                        $query->whereNull('expires_at')
                                            ->where(
                                                'reserved_at',
                                                '>',
                                                now()->subMinutes(self::RESERVATION_MINUTES)
                                            );
                                    });
                            });
                    });
            })
            ->count();

        if (
            $coupon->usage_limit_per_user !== null
            && $userUsage >= (int) $coupon->usage_limit_per_user
        ) {
            throw new RuntimeException('شما قبلاً به سقف استفاده از این کد تخفیف رسیده‌اید.');
        }

        return [
            'coupon' => $coupon,
            'discount_amount' => $this->calculateDiscount($coupon, $subtotal),
        ];
    }

    public function reserveForOrder(Coupon $coupon, int $userId, Order $order): CouponUsage
    {
        return CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'order_id' => $order->id,
            'status' => 'reserved',
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(self::RESERVATION_MINUTES),
        ]);
    }

    public function releaseExpiredReservations(): int
    {
        $now = now();
        $legacyCutoff = $now->copy()->subMinutes(self::RESERVATION_MINUTES);

        $usageIds = CouponUsage::query()
            ->where('status', 'reserved')
            ->where(function ($query) use ($now, $legacyCutoff) {
                $query->where('expires_at', '<=', $now)
                    ->orWhere(function ($query) use ($legacyCutoff) {
                        $query->whereNull('expires_at')
                            ->whereNotNull('reserved_at')
                            ->where('reserved_at', '<=', $legacyCutoff);
                    });
            })
            ->pluck('id');

        $releasedCount = 0;

        foreach ($usageIds as $usageId) {
            $released = DB::transaction(function () use ($usageId): bool {
                $usage = CouponUsage::query()
                    ->whereKey($usageId)
                    ->lockForUpdate()
                    ->first();

                if (! $usage || $usage->status !== 'reserved') {
                    return false;
                }

                $usage->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'consumed_at' => null,
                ]);

                return true;
            });

            if ($released) {
                $releasedCount++;
            }
        }

        return $releasedCount;
    }

    public function consumeForOrder(Order $order): bool
    {
        if ($order->coupon_id === null) {
            return true;
        }

        $usage = CouponUsage::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (! $usage) {
            throw new RuntimeException('رزرو کد تخفیف این سفارش پیدا نشد.');
        }

        if ($usage->status === 'consumed') {
            return true;
        }

        if ($usage->status !== 'reserved') {
            throw new RuntimeException('رزرو کد تخفیف در وضعیت قابل مصرف نیست.');
        }

        $coupon = Coupon::query()
            ->whereKey($usage->coupon_id)
            ->lockForUpdate()
            ->first();

        if (! $coupon) {
            throw new RuntimeException('کد تخفیف این سفارش پیدا نشد.');
        }

        $usage->update([
            'status' => 'consumed',
            'consumed_at' => now(),
        ]);

        $coupon->increment('used_count');

        return true;
    }

    public function releaseForOrder(Order $order): bool
    {
        if ($order->coupon_id === null) {
            return true;
        }

        $usage = CouponUsage::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (! $usage || $usage->status !== 'reserved') {
            return true;
        }

        $usage->update([
            'status' => 'released',
            'released_at' => now(),
        ]);

        return true;
    }

    protected function ensureValid(Coupon $coupon, float $subtotal): void
    {
        if (! $coupon->is_active) {
            throw new RuntimeException('این کد تخفیف فعال نیست.');
        }

        $now = Carbon::now();

        if ($coupon->starts_at !== null && $now->lt($coupon->starts_at)) {
            throw new RuntimeException('زمان استفاده از این کد تخفیف هنوز نرسیده است.');
        }

        if ($coupon->expires_at !== null && $now->gt($coupon->expires_at)) {
            throw new RuntimeException('این کد تخفیف منقضی شده است.');
        }

        if ($subtotal < (float) $coupon->min_order_amount) {
            throw new RuntimeException('مبلغ سفارش برای استفاده از این کد تخفیف کافی نیست.');
        }
    }

    protected function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        $discount = min($discount, $subtotal);

        if ($discount >= $subtotal && $subtotal > 0) {
            throw new RuntimeException('این کد تخفیف مبلغ سفارش را به صفر می‌رساند و فعلاً قابل استفاده نیست.');
        }

        return round(max($discount, 0), 2);
    }
}
