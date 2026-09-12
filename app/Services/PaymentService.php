<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected InventoryReservationService $reservationService,
        protected ?PaymentGatewayInterface $gateway = null
    ) {}

    public function create(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $order->refresh();

            if ($order->status === 'cancelled') {
                throw new RuntimeException('برای سفارش لغوشده امکان ایجاد پرداخت وجود ندارد.');
            }

            $amount = (float) $order->total_amount;
            if ($amount <= 0) {
                throw new RuntimeException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }

            $existingPayment = $order->payments()
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if ($existingPayment) {
                return $existingPayment;
            }

            /** @var Payment $payment */
            $payment = $order->payments()->create([
                'amount' => $amount,
                'gateway' => null,
                'status' => 'pending',
                'authority' => null,
                'transaction_id' => null,
                'reference_number' => null,
                'card_last_four' => null,
                'card_token' => null,
                'gateway_response' => null,
                'paid_at' => null,
                'refunded_at' => null,
            ]);

            return $payment;
        });
    }

    public function requestGatewayPayment(Payment $payment): array
    {
        $payment->refresh();

        if ($payment->status !== 'pending') {
            throw new RuntimeException('فقط پرداخت‌های در انتظار می‌توانند به درگاه ارسال شوند.');
        }

        $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
        $payment->loadMissing('order');

        if ($payment->authority !== null && $payment->authority !== '') {
            return [
                'payment' => $payment,
                'gateway' => $payment->gateway,
                'authority' => $payment->authority,
                'payment_url' => $gateway->paymentUrl([
                    'authority' => $payment->authority,
                ]),
                'gateway_response' => $payment->gateway_response,
            ];
        }

        $result = $gateway->request($payment);
        $authority = (string) ($result['authority'] ?? '');
        $paymentUrl = (string) ($result['payment_url'] ?? '');

        if ($authority === '') {
            throw new RuntimeException('درگاه پرداخت Authority معتبری برنگرداند.');
        }

        if ($paymentUrl === '') {
            throw new RuntimeException('درگاه پرداخت URL معتبری برای ادامه پرداخت برنگرداند.');
        }

        $payment->update([
            'gateway' => $this->gatewayName($gateway),
            'authority' => $authority,
            'gateway_response' => $result['response'] ?? null,
        ]);

        $payment->refresh();

        return [
            'payment' => $payment,
            'gateway' => $payment->gateway,
            'authority' => $payment->authority,
            'payment_url' => $paymentUrl,
            'gateway_response' => $payment->gateway_response,
        ];
    }

    public function verifyGatewayPayment(Payment $payment, array $callbackData): array
    {
        $payment->refresh();

        if ($payment->status !== 'pending') {
            return [
                'success' => false,
                'verified' => false,
                'payment' => $payment,
                'message' => 'این پرداخت دیگر در وضعیت pending نیست.',
            ];
        }

        $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
        $payment->loadMissing('order');

        $result = $gateway->verify($payment, $callbackData);

        if (isset($result['response'])) {
            $payment->update([
                'gateway_response' => $result['response'],
            ]);
            $payment->refresh();
        }

        return [
            'success' => (bool) ($result['success'] ?? false),
            'verified' => (bool) ($result['verified'] ?? false),
            'payment' => $payment,
            'transaction_id' => $result['transaction_id'] ?? null,
            'reference_number' => $result['reference_number'] ?? null,
            'gateway_response' => $result['response'] ?? null,
        ];
    }

    public function markAsPaid(Payment $payment, ?string $transactionId = null): bool
    {
        return DB::transaction(function () use ($payment, $transactionId) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }

            if ($payment->status === 'paid') {
                return false;
            }

            if ($payment->status !== 'pending') {
                return false;
            }

            $order = $payment->order()->lockForUpdate()->first();

            if (! $order) {
                throw new RuntimeException('سفارش مربوط به این پرداخت پیدا نشد.');
            }

            if ($order->status === 'cancelled') {
                return false;
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();

            foreach ($reservations as $reservation) {
                if (! $this->reservationService->consume($reservation)) {
                    throw new RuntimeException('مصرف رزرو موجودی سفارش انجام نشد.');
                }
            }

            $payment->update([
                'status' => 'paid',
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'confirmed_at' => $order->confirmed_at ?? now(),
            ]);

            return true;
        });
    }

    public function markAsFailed(Payment $payment, ?string $gatewayResponse = null): bool
    {
        return DB::transaction(function () use ($payment, $gatewayResponse) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }

            if ($payment->status === 'paid') {
                return false;
            }

            if ($payment->status !== 'pending') {
                return false;
            }

            $order = $payment->order()->lockForUpdate()->first();

            if (! $order) {
                throw new RuntimeException('سفارش مربوط به این پرداخت پیدا نشد.');
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();

            foreach ($reservations as $reservation) {
                if (! $this->reservationService->release($reservation)) {
                    throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                }
            }

            $payment->update([
                'status' => 'failed',
                'gateway_response' => $gatewayResponse,
            ]);

            return true;
        });
    }

    public function cancel(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }

            if ($payment->status === 'paid') {
                return false;
            }

            if ($payment->status !== 'pending') {
                return false;
            }

            $order = $payment->order()->lockForUpdate()->first();

            if (! $order) {
                throw new RuntimeException('سفارش مربوط به این پرداخت پیدا نشد.');
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();

            foreach ($reservations as $reservation) {
                if (! $this->reservationService->release($reservation)) {
                    throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                }
            }

            $payment->update([
                'status' => 'cancelled',
            ]);

            return true;
        });
    }

    protected function gatewayName(PaymentGatewayInterface $gateway): string
    {
        $class = class_basename(get_class($gateway));

        if (str_ends_with($class, 'Gateway')) {
            $class = substr($class, 0, -strlen('Gateway'));
        }

        return strtolower($class);
    }
}
