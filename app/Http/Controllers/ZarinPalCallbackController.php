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
    ) {}

    /**
     * Handle the customer return from ZarinPal.
     *
     * The callback itself does not authenticate the customer because
     * the request originates from the payment gateway.
     *
     * Payment ownership is established through the gateway authority.
     */
    public function handle(Request $request): RedirectResponse
    {
        $authority = (string) ($request->input('Authority') ?? $request->input('authority') ?? '');

        if ($authority === '') {
            return redirect()->route('checkout.show')->with('error', 'شناسه پرداخت از درگاه دریافت نشد.');
        }

        $payment = Payment::query()
            ->where('gateway', 'zarinpal')
            ->where('authority', $authority)
            ->with('order')
            ->first();

        if (! $payment) {
            return redirect()->route('checkout.show')->with('error', 'پرداخت مربوط به این تراکنش پیدا نشد.');
        }

        try {
            $result = $this->paymentService->verifyAndFinalizeGatewayPayment(
                $payment,
                $request->all()
            );

            $orderNumber = $result['payment']->order->order_number;

            if (($result['status'] ?? null) === 'already_paid') {
                return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                    ->with('success', 'این پرداخت قبلاً با موفقیت ثبت شده است.');
            }

            if (($result['status'] ?? null) === 'paid') {
                return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                    ->with('success', 'پرداخت با موفقیت انجام شد.');
            }

            if (($result['status'] ?? null) === 'failed') {
                return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                    ->with('error', 'پرداخت انجام نشد یا توسط درگاه تأیید نشد.');
            }

            if (($result['status'] ?? null) === 'cancelled') {
                return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                    ->with('info', 'این سفارش لغو شده است و پرداخت قابل ثبت نیست.');
            }

            return redirect()->route('orders.show', ['orderNumber' => $orderNumber])
                ->with('info', 'این پرداخت دیگر در وضعیت قابل پردازش نیست.');
        } catch (RuntimeException $e) {
            return redirect()->route('orders.show', ['orderNumber' => $payment->order->order_number])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Store gateway data safely as text for auditing/debugging.
     */
    protected function serializeGatewayResponse(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : 'Unknown gateway response';
    }
}
