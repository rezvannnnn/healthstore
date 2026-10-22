<?php

namespace Tests\Feature;

use App\Services\OtpService;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class OtpServiceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_send_for_same_phone_is_rejected_while_another_send_holds_the_lock(): void
    {
        $phone = '09121234567';
        $lock = Cache::lock("otp:send:{$phone}", 15);
        $this->assertTrue($lock->get());

        $provider = new class implements SmsProviderInterface
        {
            public int $calls = 0;

            public function send(string $phone, string $message): array
            {
                $this->calls++;

                return ['success' => true];
            }
        };

        try {
            $service = new OtpService($provider);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('درخواست ارسال کد تأیید دیگری برای این شماره در حال انجام است.');

            $service->send($phone);
        } finally {
            $lock->release();
        }

        $this->assertSame(0, $provider->calls);
    }
}
