<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment request on the gateway.
     *
     * Returns gateway-specific information such as:
     * - authority / token
     * - payment URL
     * - gateway response data
     *
     * The returned array is intentionally generic so that the
     * PaymentService does not depend on a specific gateway.
     */
    public function request(Payment $payment): array;

    /**
     * Verify a payment after the customer returns from the gateway.
     *
     * Returns gateway verification information such as:
     * - success state
     * - transaction/reference number
     * - raw gateway response
     */
    public function verify(Payment $payment, array $callbackData): array;

    /**
     * Return the URL where the customer must be redirected
     * to complete the payment.
     */
    public function paymentUrl(array $gatewayData): string;
}