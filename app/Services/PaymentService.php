<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Cache;
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
        $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
        $lock = Cache::lock("payment:gateway-request:{$payment->id}", 30);

        if (! $lock->get()) {
            throw new RuntimeException('درخواست پرداخت دیگری برای این تراکنش در حال انجام است.');
        }

        try {
            $payment = DB::transaction(function () use ($payment, $gateway): Payment {
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
                if (! $lockedPayment) {
                    throw new RuntimeException('پرداخت پیدا نشد.');
                }
                if ($lockedPayment->status !== 'pending') {
                    throw new RuntimeException('فقط پرداخت‌های در انتظار می‌توانند به درگاه ارسال شوند.');
                }

                $this->assertPaymentGatewayConsistency($lockedPayment, $gateway);
                $lockedPayment->loadMissing('order');

                return $lockedPayment;
            });

            if ($payment->authority !== null && $payment->authority !== '') {
                return [
                    'payment' => $payment,
                    'gateway' => $payment->gateway,
                    'authority' => $payment->authority,
                    'payment_url' => $gateway->paymentUrl(['authority' => $payment->authority]),
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

            return DB::transaction(function () use ($payment, $gateway, $authority, $paymentUrl, $result): array {
                $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
                if (! $lockedPayment) {
                    throw new RuntimeException('پرداخت پیدا نشد.');
                }
                if ($lockedPayment->status !== 'pending') {
                    throw new RuntimeException('فقط پرداخت‌های در انتظار می‌توانند به درگاه ارسال شوند.');
                }

                $this->assertPaymentGatewayConsistency($lockedPayment, $gateway);

                if ($lockedPayment->authority !== null && $lockedPayment->authority !== '') {
                    return [
                        'payment' => $lockedPayment,
                        'gateway' => $lockedPayment->gateway,
                        'authority' => $lockedPayment->authority,
                        'payment_url' => $gateway->paymentUrl(['authority' => $lockedPayment->authority]),
                        'gateway_response' => $lockedPayment->gateway_response,
                    ];
                }

                $lockedPayment->update([
                    'gateway' => $this->gatewayName($gateway),
                    'authority' => $authority,
                    'gateway_response' => $result['response'] ?? null,
                ]);
                $lockedPayment->refresh();

                return [
                    'payment' => $lockedPayment,
                    'gateway' => $lockedPayment->gateway,
                    'authority' => $lockedPayment->authority,
                    'payment_url' => $paymentUrl,
                    'gateway_response' => $lockedPayment->gateway_response,
                ];
            });
        } finally {
            $lock->release();
        }
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
            $this->assertPaymentGatewayConsistency($payment, $gateway);
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
        $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);

        $payment = DB::transaction(function () use ($payment, $gateway): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $lockedPayment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($lockedPayment->status !== 'pending') {
                return $lockedPayment;
            }

            $this->assertPaymentGatewayConsistency($lockedPayment, $gateway);
            $lockedPayment->loadMissing('order');

            return $lockedPayment;
        });

        if ($payment->status === 'paid') {
            return [
                'status' => 'already_paid',
                'payment' => $payment,
                'transaction_id' => $payment->transaction_id,
                'gateway_response' => $payment->gateway_response,
            ];
        }

        if ($payment->status !== 'pending') {
            return [
                'status' => 'not_pending',
                'payment' => $payment,
                'transaction_id' => $payment->transaction_id,
                'gateway_response' => $payment->gateway_response,
            ];
        }

        $authority = $payment->authority;
        $result = $gateway->verify($payment, $callbackData);

        return DB::transaction(function () use ($payment, $authority, $result): array {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();
            if (! $lockedPayment) {
                throw new RuntimeException('پرداخت پیدا نشد.');
            }
            if ($lockedPayment->status === 'paid') {
                return [
                    'status' => 'already_paid',
                    'payment' => $lockedPayment,
                    'transaction_id' => $lockedPayment->transaction_id,
                    'gateway_response' => $lockedPayment->gateway_response,
                ];
            }
            if ($lockedPayment->status !== 'pending') {
                return [
                    'status' => 'not_pending',
                    'payment' => $lockedPayment,
                    'transaction_id' => $lockedPayment->transaction_id,
                    'gateway_response' => $lockedPayment->gateway_response,
                ];
            }
            if ((string) $lockedPayment->authority !== (string) $authority) {
                throw new RuntimeException('اطلاعات پرداخت در زمان تأیید تغییر کرده است.');
            }

            $gateway = $this->gateway ?? app(PaymentGatewayInterface::class);
            $this->assertPaymentGatewayConsistency($lockedPayment, $gateway);

            $gatewayResponse = $result['response'] ?? null;
            if ($gatewayResponse !== null) {
                $lockedPayment->update(['gateway_response' => $gatewayResponse]);
            }

            $verified = (bool) ($result['verified'] ?? false);
            $transactionId = $result['transaction_id'] ?? null;
            $order = $lockedPayment->order()->lockForUpdate()->first();
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
                $lockedPayment->update(['status' => 'failed']);
                $lockedPayment->refresh();

                return [
                    'status' => 'failed',
                    'success' => (bool) ($result['success'] ?? false),
                    'verified' => false,
                    'payment' => $lockedPayment,
                    'transaction_id' => null,
                    'reference_number' => $result['reference_number'] ?? null,
                    'gateway_response' => $gatewayResponse,
                ];
            }

            if ($order->status === 'cancelled') {
                $reservations = $order->inventoryReservations()->where('status', 'active')->get();
                foreach ($reservations as $reservation) {
                    if (! $this->reservationService->release($reservation)) {
                        throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                    }
                }
                $couponService->releaseForOrder($order);
                $lockedPayment->update([
                    'status' => 'cancelled',
                    'transaction_id' => (string) $transactionId,
                    'reference_number' => $result['reference_number'] ?? null,
                ]);
                $lockedPayment->refresh();

                return [
                    'status' => 'cancelled',
                    'success' => false,
                    'verified' => false,
                    'payment' => $lockedPayment,
                    'transaction_id' => $transactionId,
                    'reference_number' => $result['reference_number'] ?? null,
                    'gateway_response' => $gatewayResponse,
                ];
            }

            $reservations = $order->inventoryReservations()->where('status', 'active')->get();
            foreach ($reservations as $reservation) {
                if (! $this->reservationService->consume($reservation)) {
                    throw new RuntimeException('مصرف رزرو موجودی سفارش انجام نشد.');
                }
            }
            $couponService->consumeForOrder($order);
            $now = now();
            $lockedPayment->update([
                'status' => 'paid',
                'transaction_id' => (string) $transactionId,
                'reference_number' => $result['reference_number'] ?? null,
                'paid_at' => $now,
            ]);
            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => $now,
                'confirmed_at' => $order->confirmed_at ?? $now,
            ]);
            $lockedPayment->refresh();

            return [
                'status' => 'paid',
                'success' => (bool) ($result['success'] ?? false),
                'verified' => true,
                'payment' => $lockedPayment,
                'transaction_id' => (string) $transactionId,
                'reference_number' => $result['reference_number'] ?? null,
                'gateway_response' => $gatewayResponse,
            ];
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
                $reservations = $order->inventoryReservations()->where('status', 'active')->get();
                foreach ($reservations as $reservation) {
                    if (! $this->reservationService->release($reservation)) {
                        throw new RuntimeException('آزادسازی رزرو موجودی سفارش انجام نشد.');
                    }
                }
                ($this->couponService ?? app(CouponService::class))->releaseForOrder($order);
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

    protected function assertPaymentGatewayConsistency(Payment $payment, PaymentGatewayInterface $gateway): void
    {
        $gatewayName = $this->gatewayName($gateway);

        if ($payment->gateway !== null && $payment->gateway !== $gatewayName) {
            throw new RuntimeException('درگاه پرداخت این تراکنش با درگاه فعال سامانه مطابقت ندارد.');
        }
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
