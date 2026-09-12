<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinPalGateway implements PaymentGatewayInterface
{
    public function request(Payment $payment): array
    {
        $merchantId = (string) config('services.zarinpal.merchant_id');

        if ($merchantId === '') {
            throw new RuntimeException(
                'Merchant ID زرین‌پال تنظیم نشده است.'
            );
        }

        $amount = (int) round((float) $payment->amount);

        if ($amount <= 0) {
            throw new RuntimeException(
                'مبلغ پرداخت برای ارسال به زرین‌پال باید بیشتر از صفر باشد.'
            );
        }

        $callbackUrl = $this->callbackUrl();

        $endpoint = $this->requestEndpoint();

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'description' => sprintf(
                'پرداخت سفارش %s',
                $payment->order->order_number
            ),
            'callback_url' => $callbackUrl,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'ارتباط با درگاه زرین‌پال برقرار نشد.',
                previous: $e
            );
        }

        $body = $this->decodeResponse($response);

        if (! $response->successful()) {
            throw new RuntimeException(
                $this->gatewayErrorMessage(
                    $body,
                    $response
                )
            );
        }

        $errors = $body['errors'] ?? [];

        if (! empty($errors)) {
            throw new RuntimeException(
                $this->gatewayErrorMessage(
                    $body,
                    $response
                )
            );
        }

        $data = $body['data'] ?? [];

        $code = (int) ($data['code'] ?? 0);
        $authority = (string) ($data['authority'] ?? '');

        if ($code !== 100 || $authority === '') {
            throw new RuntimeException(
                'زرین‌پال درخواست پرداخت را نپذیرفت.'
            );
        }

        return [
            'success' => true,
            'authority' => $authority,
            'payment_url' => $this->paymentUrl([
                'authority' => $authority,
            ]),
            'response' => $body,
        ];
    }

    public function verify(
        Payment $payment,
        array $callbackData
    ): array {
        $merchantId = (string) config('services.zarinpal.merchant_id');

        if ($merchantId === '') {
            throw new RuntimeException(
                'Merchant ID زرین‌پال تنظیم نشده است.'
            );
        }

        $authority = (string) (
            $callbackData['Authority']
            ?? $callbackData['authority']
            ?? ''
        );

        $status = strtoupper((string) (
            $callbackData['Status']
            ?? $callbackData['status']
            ?? ''
        ));

        if ($authority === '') {
            return [
                'success' => false,
                'verified' => false,
                'authority' => null,
                'transaction_id' => null,
                'reference_number' => null,
                'response' => [
                    'message' => 'Authority از Callback دریافت نشد.',
                ],
            ];
        }

        /*
         * The customer may cancel the payment on the gateway.
         * In that case there is no reason to call Verify.
         */
        if ($status !== 'OK') {
            return [
                'success' => false,
                'verified' => false,
                'authority' => $authority,
                'transaction_id' => null,
                'reference_number' => null,
                'response' => [
                    'status' => $status,
                    'message' => 'پرداخت توسط کاربر تکمیل نشد.',
                ],
            ];
        }

        $amount = (int) round((float) $payment->amount);

        if ($amount <= 0) {
            throw new RuntimeException(
                'مبلغ پرداخت برای Verify معتبر نیست.'
            );
        }

        $endpoint = $this->verifyEndpoint();

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'authority' => $authority,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'ارتباط با زرین‌پال برای Verify برقرار نشد.',
                previous: $e
            );
        }

        $body = $this->decodeResponse($response);

        if (! $response->successful()) {
            throw new RuntimeException(
                $this->gatewayErrorMessage(
                    $body,
                    $response
                )
            );
        }

        $errors = $body['errors'] ?? [];

        if (! empty($errors)) {
            return [
                'success' => false,
                'verified' => false,
                'authority' => $authority,
                'transaction_id' => null,
                'reference_number' => null,
                'response' => $body,
            ];
        }

        $data = $body['data'] ?? [];

        $code = (int) ($data['code'] ?? 0);

        /*
         * ZarinPal uses:
         * 100 = successful first verification
         * 101 = already verified / successful verification
         */
        $verified = in_array($code, [100, 101], true);

        return [
            'success' => $verified,
            'verified' => $verified,
            'authority' => $authority,
            'transaction_id' => isset($data['ref_id'])
                ? (string) $data['ref_id']
                : null,
            'reference_number' => isset($data['ref_id'])
                ? (string) $data['ref_id']
                : null,
            'card_pan' => $data['card_pan'] ?? null,
            'response' => $body,
        ];
    }

    public function paymentUrl(array $gatewayData): string
    {
        $authority = (string) ($gatewayData['authority'] ?? '');

        if ($authority === '') {
            throw new RuntimeException(
                'Authority برای ایجاد آدرس پرداخت وجود ندارد.'
            );
        }

        $baseUrl = rtrim(
            (string) config(
                'services.zarinpal.payment_base_url'
            ),
            '/'
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'آدرس صفحه پرداخت زرین‌پال تنظیم نشده است.'
            );
        }

        return $baseUrl.'/pg/StartPay/'.$authority;
    }

    private function requestEndpoint(): string
    {
        $endpoint = (string) config(
            'services.zarinpal.request_endpoint'
        );

        if ($endpoint === '') {
            throw new RuntimeException(
                'Endpoint درخواست زرین‌پال تنظیم نشده است.'
            );
        }

        return $endpoint;
    }

    private function verifyEndpoint(): string
    {
        $endpoint = (string) config(
            'services.zarinpal.verify_endpoint'
        );

        if ($endpoint === '') {
            throw new RuntimeException(
                'Endpoint Verify زرین‌پال تنظیم نشده است.'
            );
        }

        return $endpoint;
    }

    private function callbackUrl(): string
    {
        $configuredUrl = trim(
            (string) config(
                'services.zarinpal.callback_url'
            )
        );

        if ($configuredUrl === '') {
            throw new RuntimeException(
                'Callback URL زرین‌پال تنظیم نشده است.'
            );
        }

        /*
         * Allow both:
         * /payment/zarinpal/callback
         * https://example.com/payment/zarinpal/callback
         */
        if (
            str_starts_with(
                $configuredUrl,
                'http://'
            )
            ||
            str_starts_with(
                $configuredUrl,
                'https://'
            )
        ) {
            return $configuredUrl;
        }

        return url(ltrim($configuredUrl, '/'));
    }

    private function decodeResponse(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            throw new RuntimeException(
                'پاسخ نامعتبر از زرین‌پال دریافت شد.'
            );
        }

        return $body;
    }

    private function gatewayErrorMessage(
        array $body,
        Response $response
    ): string {
        $message = $body['errors']['message']
            ?? $body['data']['message']
            ?? null;

        if (is_string($message) && $message !== '') {
            return 'خطای زرین‌پال: '.$message;
        }

        return sprintf(
            'زرین‌پال با کد HTTP %d پاسخ ناموفق برگرداند.',
            $response->status()
        );
    }
}
