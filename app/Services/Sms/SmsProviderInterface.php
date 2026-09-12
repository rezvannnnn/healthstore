<?php

namespace App\Services\Sms;

interface SmsProviderInterface
{
    /**
     * Send an SMS message to a phone number.
     *
     * Returns provider-specific information such as
     * message ID or provider response.
     */
    public function send(
        string $phone,
        string $message
    ): array;
}