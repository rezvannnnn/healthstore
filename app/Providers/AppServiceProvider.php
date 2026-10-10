<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StoreSetting;
use App\Models\User;
use App\Observers\StoreObserver;
use App\Services\Payment\DisabledGateway;
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
            fn () => config('services.payment.provider') === 'zarinpal'
                ? new ZarinPalGateway
                : new DisabledGateway
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
        foreach ([Product::class, Category::class, Brand::class, Article::class, ArticleCategory::class, ProductPrice::class, Order::class, User::class, StoreSetting::class] as $model) {
            $model::observe(StoreObserver::class);
        }
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
