<?php

namespace App\Services\Sms;

use RuntimeException;

class NotConfiguredSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): array
    {
        throw new RuntimeException(
            'ارائه‌دهنده پیامک برای محیط production پیکربندی نشده است.'
        );
    }
}
