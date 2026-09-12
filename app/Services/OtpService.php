<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class OtpService
{
    public function __construct(
        protected SmsProviderInterface $smsProvider
    ) {}

    public function send(string $phone): array
    {
        $phone = $this->normalizePhone($phone);
        $codeLength = (int) config('otp.code_length', 6);
        $expiresInMinutes = (int) config('otp.expires_in_minutes', 5);
        $resendCooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $maxRequestsPerHour = (int) config('otp.max_requests_per_hour', 5);

        $requestsInLastHour = OtpVerification::query()->where('phone', $phone)
            ->where('created_at', '>=', now()->subHour())->count();
        if ($requestsInLastHour >= $maxRequestsPerHour) {
            throw new RuntimeException('تعداد درخواست‌های کد تأیید برای این شماره در یک ساعت بیش از حد مجاز است.');
        }

        $latestVerification = OtpVerification::query()->where('phone', $phone)->latest('id')->first();
        if ($latestVerification) {
            $cooldownEndsAt = $latestVerification->created_at->copy()->addSeconds($resendCooldownSeconds);
            if (now()->lt($cooldownEndsAt)) {
                $remainingSeconds = max(1, now()->diffInSeconds($cooldownEndsAt, false));
                throw new RuntimeException(sprintf('برای دریافت کد جدید باید %d ثانیه صبر کنید.', $remainingSeconds));
            }
        }

        $code = $this->generateCode($codeLength);
        OtpVerification::query()->where('phone', $phone)->whereNull('verified_at')->update(['verified_at' => now()]);

        $verification = OtpVerification::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        $smsResult = $this->smsProvider->send($phone, sprintf('کد تأیید شما: %s', $code));
        if (($smsResult['success'] ?? false) !== true) {
            $verification->update(['verified_at' => now()]);
            throw new RuntimeException('ارسال پیامک کد تأیید انجام نشد.');
        }

        return ['verification' => $verification, 'sms' => $smsResult];
    }

    public function verify(string $phone, string $code): bool
    {
        $phone = $this->normalizePhone($phone);
        $maxAttempts = (int) config('otp.max_attempts', 5);
        $verification = OtpVerification::query()->where('phone', $phone)->whereNull('verified_at')->latest('id')->first();
        if (! $verification || $verification->expires_at->isPast() || $verification->hasExceededAttempts($maxAttempts)) {
            return false;
        }
        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');
            return false;
        }
        $verification->update(['verified_at' => now()]);
        return true;
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', trim($phone)) ?? '';
        if ($phone === '') {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }
        if (str_starts_with($phone, '0098')) {
            $phone = '0'.substr($phone, 4);
        } elseif (str_starts_with($phone, '98')) {
            $phone = '0'.substr($phone, 2);
        }
        if (! preg_match('/^09\d{9}$/', $phone)) {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }
        return $phone;
    }

    protected function generateCode(int $length): string
    {
        if ($length < 4 || $length > 8) {
            throw new RuntimeException('طول OTP باید بین ۴ تا ۸ رقم باشد.');
        }
        $min = (int) ('1'.str_repeat('0', $length - 1));
        $max = (int) str_repeat('9', $length);
        return (string) random_int($min, $max);
    }
}
