<?php

use App\Models\User;
use App\Services\CouponService;
use App\Services\OtpService;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:release-expired-reservations', function (InventoryReservationService $service) {
    $releasedCount = $service->releaseExpired();

    $this->info("Released {$releasedCount} expired inventory reservation(s).");
})->purpose('Release expired inventory reservations');

Artisan::command('coupon:release-expired-reservations', function (CouponService $service) {
    $releasedCount = $service->releaseExpiredReservations();

    $this->info("Released {$releasedCount} expired coupon reservation(s).");
})->purpose('Release expired coupon reservations');

Artisan::command('admin:create', function (OtpService $otpService) {
    $name = trim((string) $this->ask('نام مدیر'));
    $phoneInput = trim((string) $this->ask('شماره موبایل مدیر'));
    $emailInput = trim((string) $this->ask('ایمیل مدیر (اختیاری)'));
    $email = $emailInput !== '' ? $emailInput : null;

    if ($name === '') {
        $this->fail('نام مدیر الزامی است.');
    }

    try {
        $phone = $otpService->normalizePhone($phoneInput);
    } catch (RuntimeException $exception) {
        $this->fail($exception->getMessage());
    }

    if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $this->fail('ایمیل واردشده معتبر نیست.');
    }

    $user = User::query()->where('phone', $phone)->first();

    if ($user) {
        $attributes = ['is_admin' => true];

        if ($user->phone_verified_at === null) {
            $attributes['phone_verified_at'] = now();
        }

        if ($email !== null) {
            $attributes['email'] = $email;
        }

        $user->update($attributes);
        $this->info("کاربر {$user->id} اکنون مدیر سیستم است.");

        return;
    }

    $user = User::create([
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'phone_verified_at' => now(),
        'password' => null,
        'is_admin' => true,
    ]);

    $this->info("مدیر سیستم با شناسه {$user->id} ایجاد شد.");
})->purpose('Create or promote an administrator account');
