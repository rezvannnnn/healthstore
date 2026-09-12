<?php

namespace App\Providers;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\ZarinPalGateway;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * Payment gateway abstraction.
         */
        $this->app->bind(
            PaymentGatewayInterface::class,
            ZarinPalGateway::class
        );

        /*
         * SMS provider abstraction.
         *
         * FakeSmsProvider is currently used while the real SMS
         * provider has not been selected/configured yet.
         */
        $this->app->bind(
            SmsProviderInterface::class,
            FakeSmsProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
