<?php

namespace App\Providers;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\ZarinPalGateway;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\NotConfiguredSmsProvider;
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
         * Keep development/tests deterministic while failing closed in
         * production until a real provider is configured.
         */
        $this->app->bind(SmsProviderInterface::class, function () {
            $provider = (string) config('services.sms.provider', 'fake');

            if (app()->isProduction() && $provider === 'fake') {
                return new NotConfiguredSmsProvider;
            }

            return $provider === 'fake'
                ? new FakeSmsProvider
                : new NotConfiguredSmsProvider;
        });
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
