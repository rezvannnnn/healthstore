<?php

namespace Tests\Feature;

use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\NotConfiguredSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Tests\TestCase;

class SmsProviderConfigurationTest extends TestCase
{
    public function test_testing_environment_uses_fake_sms_provider(): void
    {
        config(['services.sms.provider' => 'fake']);

        $this->assertInstanceOf(
            FakeSmsProvider::class,
            app(SmsProviderInterface::class),
        );
    }

    public function test_production_environment_fails_closed_when_fake_sms_provider_is_selected(): void
    {
        config(['services.sms.provider' => 'fake']);

        $this->app->detectEnvironment(fn (): string => 'production');

        $this->assertInstanceOf(
            NotConfiguredSmsProvider::class,
            app(SmsProviderInterface::class),
        );
    }
}
