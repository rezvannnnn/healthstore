<?php

namespace App\Services\Sms;

class FakeSmsProvider implements SmsProviderInterface
{
    /**
     * Keep sent messages in memory for tests/development.
     */
    protected array $messages = [];

    /**
     * Send an SMS message.
     */
    public function send(
        string $phone,
        string $message
    ): array {
        $messageId = uniqid(
            'fake_sms_',
            true
        );

        $this->messages[] = [
            'id' => $messageId,
            'phone' => $phone,
            'message' => $message,
        ];

        return [
            'success' => true,
            'message_id' => $messageId,
            'provider' => 'fake',
            'response' => [
                'phone' => $phone,
                'message' => $message,
            ],
        ];
    }

    /**
     * Return all messages sent through this fake provider.
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * Return the last sent message.
     */
    public function lastMessage(): ?array
    {
        if ($this->messages === []) {
            return null;
        }

        return end($this->messages);
    }
}
