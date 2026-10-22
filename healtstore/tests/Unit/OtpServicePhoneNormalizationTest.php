<?php

namespace Tests\Unit;

use App\Services\OtpService;
use App\Services\Sms\SmsProviderInterface;
use Tests\TestCase;

class OtpServicePhoneNormalizationTest extends TestCase
{
    public function test_persian_digits_are_normalized(): void
    {
        $service = new OtpService(new class implements SmsProviderInterface
        {
            public function send(string $phone, string $message): array
            {
                return ['success' => true];
            }
        });

        $this->assertSame(
            '09121234567',
            $service->normalizePhone('۰۹۱۲۱۲۳۴۵۶۷')
        );
    }

    public function test_arabic_digits_are_normalized(): void
    {
        $service = new OtpService(new class implements SmsProviderInterface
        {
            public function send(string $phone, string $message): array
            {
                return ['success' => true];
            }
        });

        $this->assertSame(
            '09121234567',
            $service->normalizePhone('٠٩١٢١٢٣٤٥٦٧')
        );
    }

    public function test_international_prefix_is_normalized_after_digit_conversion(): void
    {
        $service = new OtpService(new class implements SmsProviderInterface
        {
            public function send(string $phone, string $message): array
            {
                return ['success' => true];
            }
        });

        $this->assertSame(
            '09121234567',
            $service->normalizePhone('۹۸۹۱۲۱۲۳۴۵۶۷')
        );
    }
}
