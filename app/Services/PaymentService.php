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
        protected ?PaymentGatewayInterface $gateway = null,
        protected ?CouponService $couponService = null
    ) {}

    public function create(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $lockedOrder) {
                throw new RuntimeException('سفارش پیدا نشد.');
            }
            if ($lockedOrder->status === 'cancelled') {
                throw new RuntimeException('برای سفارش لغوشده امکان ایجاد پرداخت وجود ندارد.');
            }
            if ($lockedOrder->status === 'paid' || $lockedOrder->payment_status === 'paid') {
                throw new RuntimeException('این سفارش قبلاً پرداخت شده است.');
            }
            if ($lockedOrder->status !== 'pending') {
                throw new RuntimeException('فقط سفارش‌های در انتظار می‌توانند وارد فرایند پرداخت شوند.');
            }
            $amount = (float) $lockedOrder->total_amount;
            if ($amount <= 0) {
                throw new RuntimeException('مبلغ پرداخت باید بیشتر از صفر باشد.');
            }
            $existingPayment = $lockedOrder->payments()->where('status', 'pending')->latest('id')->first();
            if ($existingPayment) {
                return $existingPayment;
            }

            return $lockedOrder->payments()->create([
                'amount' => $amount, 'gateway' => null, 'status' => 'pending', 'authority' => null,
                'transaction_id' => null, 'reference_number' => null, 'card_last_four' => null,
                'card_token' => null, 'gateway_response' => null, 'paid_at' => null, 'refunded_at' => null,
            ]);
        });
    }

    public function requestGatewayPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment): array {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status !== 'pending') {
                throw new RuntimeException('فقط پرداخت‌های در انتظار می‌توانند به درگاه ارسال شوند.');
            }
            $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
            $payment->loadMissing('order');
            if ($payment->authority !== null && $payment->authority !== '') {
                return ['payment' => $payment, 'gateway' => $payment->gateway, 'authority' => $payment->authority, 'payment_url' => $gateway->paymentUrl(['authority' => $payment->authority]), 'gateway_response' => $payment->gateway_response];
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
            $payment->update(['gateway' => $this->gatewayName($gateway), 'authority' => $authority, 'gateway_response' => $result['response'] ?? null]);
            $payment->refresh();

            return ['payment' => $payment, 'gateway' => $payment->gateway, 'authority' => $payment->authority, 'payment_url' => $paymentUrl, 'gateway_response' => $payment->gateway_response];
        });
    }

    public function verifyGatewayPayment(Payment $payment, array $callbackData): array
    {
        return DB::transaction(function () use ($payment, $callbackData): array {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status !== 'pending') {
                return ['success' => false, 'verified' => false, 'payment' => $payment, 'message' => 'این پرداخت دیگر در وضعیت pending نیست.'];
            }
            $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
            $payment->loadMissing('order');
            $result = $gateway->verify($payment, $callbackData);
            if (isset($result['response'])) {
                $payment->update(['gateway_response' => $result['response']]);
                $payment->refresh();
            }

            return ['success' => (bool) ($result['success'] ?? false), 'verified' => (bool) ($result['verified'] ?? false), 'payment' => $payment, 'transaction_id' => $result['transaction_id'] ?? null, 'reference_number' => $result['reference_number'] ?? null, 'gateway_response' => $result['response'] ?? null];
        });
    }

    public function verifyAndFinalizeGatewayPayment(Payment $payment, array $callbackData): array
    {
        return DB::transaction(function () use ($payment, $callbackData): array {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status === 'paid') {
                return ['status' => 'already_paid', 'payment' => $payment, 'transaction_id' => $payment->transaction_id, 'gateway_response' => $payment->gateway_response];
            }
            if ($payment->status !== 'pending') {
                return ['status' => 'not_pending', 'payment' => $payment, 'transaction_id' => $payment->transaction_id, 'gateway_response' => $payment->gateway_response];
            }

            $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
            $payment->loadMissing('order');
            $result = $gateway->verify($payment, $callbackData);
            $gatewayResponse = $result['response'] ?? null;
            if ($gatewayResponse !== null) {
                $payment->update(['gateway_response' => $gatewayResponse]);
            }

            $verified = (bool) ($result['verified'] ?? false);
            $transactionId = $result['transaction_id'] ?? null;
            $order = $payment->order()->lockForUpdate()->first();
            if (! $order) {
                throw new RuntimeException('سفارش مربوط به این پرداخت پیدا نشد.');
            }
            $couponService = $this->couponService ?? app(CouponService::class);

            if (! $verified || empty($transactionId)) {
                $reservations = $order->inventoryReservations()->where('status', 'active')->get();
                foreach ($reservations as $reservation) {
                    if (! $this->reservationService->release($reservation)) {
                        throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                    }
                }
                $couponService->releaseForOrder($order);
                $payment->update(['status' => 'failed']);
                $payment->refresh();

                return ['status' => 'failed', 'success' => (bool) ($result['success'] ?? false), 'verified' => false, 'payment' => $payment, 'transaction_id' => null, 'reference_number' => $result['reference_number'] ?? null, 'gateway_response' => $gatewayResponse];
            }

            if ($order->status === 'cancelled') {
                $reservations = $order->inventoryReservations()->where('status', 'active')->get();
                foreach ($reservations as $reservation) {
                    if (! $this->reservationService->release($reservation)) {
                        throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                    }
                }
                $couponService->releaseForOrder($order);
                $payment->update(['status' => 'cancelled', 'transaction_id' => (string) $transactionId, 'reference_number' => $result['reference_number'] ?? null]);
                $payment->refresh();

                return ['status' => 'cancelled', 'success' => false, 'verified' => false, 'payment' => $payment, 'transaction_id' => $transactionId, 'reference_number' => $result['reference_number'] ?? null, 'gateway_response' => $gatewayResponse];
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();
            foreach ($reservations as $reservation) {
                if (! $this->reservationService->consume($reservation)) {
                    throw new RuntimeException('مصرف رزرو موجودی سفارش انجام نشد.');
                }
            }
            $couponService->consumeForOrder($order);
            $now = now();
            $payment->update(['status' => 'paid', 'transaction_id' => (string) $transactionId, 'reference_number' => $result['reference_number'] ?? null, 'paid_at' => $now]);
            $order->update(['status' => 'paid', 'payment_status' => 'paid', 'paid_at' => $now, 'confirmed_at' => $order->confirmed_at ?? $now]);
            $payment->refresh();

            return ['status' => 'paid', 'success' => (bool) ($result['success'] ?? false), 'verified' => true, 'payment' => $payment, 'transaction_id' => (string) $transactionId, 'reference_number' => $result['reference_number'] ?? null, 'gateway_response' => $gatewayResponse];
        });
    }

    public function markAsPaid(Payment $payment, ?string $transactionId = null): bool
    {
        return DB::transaction(function () use ($payment, $transactionId) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status === 'paid' || $payment->status !== 'pending') {
                return false;
            }
            $order = $payment->order()->lockForUpdate()->first();
            if (! $order) {
                throw new RuntimeException('سفارش مربوط به این پرداخت پیدا نشد.');
            }
            if ($order->status === 'cancelled') {
                $payment->update(['status' => 'cancelled', 'transaction_id' => $transactionId]);

                return false;
            }
            $reservations = $order->inventoryReservations()->where('status', 'active')->get();
            foreach ($reservations as $reservation) {
                if (! $this->reservationService->consume($reservation)) {
                    throw new RuntimeException('مصرف رزرو موجودی سفارش انجام نشد.');
                }
            }
            ($this->couponService ?? app(CouponService::class))->consumeForOrder($order);
            $now = now();
            $payment->update(['status' => 'paid', 'transaction_id' => $transactionId, 'paid_at' => $now]);
            $order->update(['status' => 'paid', 'payment_status' => 'paid', 'paid_at' => $now, 'confirmed_at' => $order->confirmed_at ?? $now]);

            return true;
        });
    }

    public function markAsFailed(Payment $payment, ?string $gatewayResponse = null): bool
    {
        return DB::transaction(function () use ($payment, $gatewayResponse) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status === 'paid' || $payment->status !== 'pending') {
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
            ($this->couponService ?? app(CouponService::class))->releaseForOrder($order);
            $payment->update(['status' => 'failed', 'gateway_response' => $gatewayResponse]);

            return true;
        });
    }

    public function cancel(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $payment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($payment->status === 'paid' || $payment->status !== 'pending') {
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
            ($this->couponService ?? app(CouponService::class))->releaseForOrder($order);
            $payment->update(['status' => 'cancelled']);

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
