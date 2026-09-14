<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Start the payment process for an order.
     */
    public function start(Request $request, string $orderNumber): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $order = Order::query()->where('order_number', $orderNumber)
            ->where('user_id', $user->id)->firstOrFail();

        try {
            $payment = $this->paymentService->create($order);
            $gatewayPayment = $this->paymentService->requestGatewayPayment($payment);
        } catch (RuntimeException $e) {
            return redirect()->route('orders.show', [
                'orderNumber' => $order->order_number,
            ])->with('error', $e->getMessage());
        }

        return redirect()->away($gatewayPayment['payment_url']);
    }
}
