<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Start the payment process for an order.
     *
     * The payment record is created first and then submitted
     * to the configured payment gateway.
     */
    public function start(
        Request $request,
        string $orderNumber
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user, 401);

        /*
         * Only the owner of the order may start its payment.
         */
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        /*
         * Create or reuse the internal pending payment.
         */
        $payment = $this->paymentService->create($order);

        /*
         * Send the pending payment to the configured gateway.
         *
         * This stores the gateway authority on the Payment record
         * while keeping the payment itself pending until callback
         * verification succeeds.
         */
        $gatewayPayment = $this->paymentService->requestGatewayPayment(
            $payment
        );

        return redirect()->away(
            $gatewayPayment['payment_url']
        );
    }
}