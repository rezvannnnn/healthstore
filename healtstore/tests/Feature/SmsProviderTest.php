<?php

namespace Tests\Feature;

use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_provider_interface_resolves_to_fake_provider(): void
    {
        $provider = app(
            SmsProviderInterface::class
        );

        $this->assertInstanceOf(
            FakeSmsProvider::class,
            $provider
        );
    }

    public function test_fake_sms_provider_can_send_a_message(): void
    {
        $provider = app(
            SmsProviderInterface::class
        );

        $result = $provider->send(
            '09121234567',
            'کد تأیید شما: 123456'
        );

        $this->assertTrue(
            $result['success']
        );

        $this->assertEquals(
            'fake',
            $result['provider']
        );

        $this->assertNotEmpty(
            $result['message_id']
        );

        $messages = $provider->messages();

        $this->assertCount(
            1,
            $messages
        );

        $this->assertEquals(
            '09121234567',
            $messages[0]['phone']
        );

        $this->assertEquals(
            'کد تأیید شما: 123456',
            $messages[0]['message']
        );
    }

    public function test_fake_sms_provider_can_return_last_message(): void
    {
        $provider = app(
            SmsProviderInterface::class
        );

        $provider->send(
            '09121234567',
            'پیام اول'
        );

        $provider->send(
            '09129876543',
            'پیام دوم'
        );

        $lastMessage = $provider->lastMessage();

        $this->assertNotNull(
            $lastMessage
        );

        $this->assertEquals(
            '09129876543',
            $lastMessage['phone']
        );

        $this->assertEquals(
            'پیام دوم',
            $lastMessage['message']
        );
    }
}
