<?php

namespace App\Services\Payment;

use App\Models\Payment;
use RuntimeException;

class DisabledGateway implements PaymentGatewayInterface
{
    public function request(Payment $payment): array
    {
        throw new RuntimeException('درگاه پرداخت هنوز فعال نشده است. سفارش ثبت شده و در انتظار فعال‌سازی پرداخت است.');
    }

    public function verify(Payment $payment, array $callbackData): array
    {
        throw new RuntimeException('درگاه پرداخت فعال نیست.');
    }

    public function paymentUrl(array $gatewayData): string
    {
        throw new RuntimeException('درگاه پرداخت فعال نیست.');
    }
}
