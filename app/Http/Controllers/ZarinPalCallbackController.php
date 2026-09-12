<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ZarinPalCallbackController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Handle the customer return from ZarinPal.
     *
     * The callback itself does not authenticate the customer because
     * the request originates from the payment gateway.
     *
     * Payment ownership is established through the gateway authority.
     */
    public function handle(
        Request $request
    ): RedirectResponse {
        $authority = (string) (
            $request->input('Authority')
            ?? $request->input('authority')
            ?? ''
        );

        if ($authority === '') {
            return redirect()
                ->route('checkout.show')
                ->with(
                    'error',
                    'شناسه پرداخت از درگاه دریافت نشد.'
                );
        }

        $payment = Payment::query()
            ->where('gateway', 'zarinpal')
            ->where('authority', $authority)
            ->with('order')
            ->first();

        if (! $payment) {
            return redirect()
                ->route('checkout.show')
                ->with(
                    'error',
                    'پرداخت مربوط به این تراکنش پیدا نشد.'
                );
        }

        /*
         * A callback may be sent more than once.
         * Do not process an already completed payment again.
         */
        if ($payment->status === 'paid') {
            return redirect()
                ->route('orders.show', [
                    'orderNumber' => $payment->order->order_number,
                ])
                ->with(
                    'success',
                    'این پرداخت قبلاً با موفقیت ثبت شده است.'
                );
        }

        if ($payment->status !== 'pending') {
            return redirect()
                ->route('orders.show', [
                    'orderNumber' => $payment->order->order_number,
                ])
                ->with(
                    'info',
                    'این پرداخت دیگر در وضعیت قابل پردازش نیست.'
                );
        }

        try {
            $verification = $this->paymentService->verifyGatewayPayment(
                $payment,
                $request->all()
            );

            if (
                ($verification['verified'] ?? false) === true
                &&
                ! empty($verification['transaction_id'])
            ) {
                $this->paymentService->markAsPaid(
                    $payment,
                    (string) $verification['transaction_id']
                );

                return redirect()
                    ->route('orders.show', [
                        'orderNumber' => $payment->order->order_number,
                    ])
                    ->with(
                        'success',
                        'پرداخت با موفقیت انجام شد.'
                    );
            }

            /*
             * Verification failed or the customer did not complete
             * the payment. Release the inventory reservation.
             */
            $gatewayResponse = $verification['gateway_response']
                ?? $request->all();

            $this->paymentService->markAsFailed(
                $payment,
                $this->serializeGatewayResponse(
                    $gatewayResponse
                )
            );

            return redirect()
                ->route('orders.show', [
                    'orderNumber' => $payment->order->order_number,
                ])
                ->with(
                    'error',
                    'پرداخت انجام نشد یا توسط درگاه تأیید نشد.'
                );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('orders.show', [
                    'orderNumber' => $payment->order->order_number,
                ])
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /**
     * Store gateway data safely as text for auditing/debugging.
     */
    protected function serializeGatewayResponse(
        mixed $response
    ): string {
        if (is_string($response)) {
            return $response;
        }

        $json = json_encode(
            $response,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $json !== false
            ? $json
            : 'Unknown gateway response';
    }
}