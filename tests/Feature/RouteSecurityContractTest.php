<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RouteSecurityContractTest extends TestCase
{
    /**
     * @return array<string, array{string, string[]}> 
     */
    public static function protectedRouteProvider(): array
    {
        return [
            'checkout show' => ['checkout.show', ['auth']],
            'checkout confirm' => ['checkout.confirm', ['auth']],
            'checkout reject' => ['checkout.reject', ['auth']],
            'order show' => ['orders.show', ['auth']],
            'order cancel' => ['orders.cancel', ['auth']],
            'order payment start' => ['orders.payment.start', ['auth', 'throttle:10,1']],
            'cart index' => ['cart.index', ['auth']],
            'cart item store' => ['cart.items.store', ['auth']],
            'cart item update' => ['cart.items.update', ['auth']],
            'cart item destroy' => ['cart.items.destroy', ['auth']],
            'account orders' => ['account.orders.index', ['auth']],
            'account addresses' => ['account.addresses.index', ['auth']],
            'account address store' => ['account.addresses.store', ['auth']],
            'account address update' => ['account.addresses.update', ['auth']],
            'account address delete' => ['account.addresses.destroy', ['auth']],
            'account profile' => ['account.profile.index', ['auth']],
            'account profile update' => ['account.profile.update', ['auth']],
            'admin dashboard' => ['admin.dashboard', ['auth', 'admin']],
            'admin products index' => ['admin.products.index', ['auth', 'admin']],
            'admin products create' => ['admin.products.create', ['auth', 'admin']],
            'admin products store' => ['admin.products.store', ['auth', 'admin']],
            'admin products edit' => ['admin.products.edit', ['auth', 'admin']],
            'admin products update' => ['admin.products.update', ['auth', 'admin']],
            'admin categories index' => ['admin.categories.index', ['auth', 'admin']],
            'admin categories create' => ['admin.categories.create', ['auth', 'admin']],
            'admin categories store' => ['admin.categories.store', ['auth', 'admin']],
            'admin categories edit' => ['admin.categories.edit', ['auth', 'admin']],
            'admin categories update' => ['admin.categories.update', ['auth', 'admin']],
            'admin brands index' => ['admin.brands.index', ['auth', 'admin']],
            'admin brands create' => ['admin.brands.create', ['auth', 'admin']],
            'admin brands store' => ['admin.brands.store', ['auth', 'admin']],
            'admin brands update' => ['admin.brands.update', ['auth', 'admin']],
            'admin brands edit' => ['admin.brands.edit', ['auth', 'admin']],
            'admin inventory index' => ['admin.inventory.index', ['auth', 'admin']],
            'admin inventory store' => ['admin.inventory.store', ['auth', 'admin']],
            'admin inventory adjust' => ['admin.inventory.adjust', ['auth', 'admin']],
            'admin inventory movements' => ['admin.inventory.movements', ['auth', 'admin']],
            'admin orders index' => ['admin.orders.index', ['auth', 'admin']],
            'admin orders show' => ['admin.orders.show', ['auth', 'admin']],
            'admin orders status' => ['admin.orders.status', ['auth', 'admin']],
            'admin payments index' => ['admin.payments.index', ['auth', 'admin']],
            'admin payments show' => ['admin.payments.show', ['auth', 'admin']],
            'admin customers index' => ['admin.customers.index', ['auth', 'admin']],
            'admin customers show' => ['admin.customers.show', ['auth', 'admin']],
            'admin coupons index' => ['admin.coupons.index', ['auth', 'admin']],
            'admin coupons store' => ['admin.coupons.store', ['auth', 'admin']],
            'admin coupons update' => ['admin.coupons.update', ['auth', 'admin']],
            'admin coupons delete' => ['admin.coupons.destroy', ['auth', 'admin']],
            'admin settings index' => ['admin.settings.index', ['auth', 'admin']],
            'admin settings update' => ['admin.settings.update', ['auth', 'admin']],
        ];
    }

    /** @dataProvider protectedRouteProvider */
    public function test_protected_route_has_expected_middleware(string $name, array $expected): void
    {
        $route = RouteFacade::getRoutes()->getByName($name);

        $this->assertInstanceOf(Route::class, $route);
        $middleware = $route->gatherMiddleware();

        foreach ($expected as $required) {
            $matches = $required === 'auth'
                ? array_filter($middleware, static fn (string $value): bool => $value === 'auth' || str_ends_with($value, '\\Authenticate'))
                : array_filter($middleware, static fn (string $value): bool => $value === $required);

            $this->assertNotEmpty($matches, "Route {$name} is missing middleware {$required}.");
        }
    }

    /** @dataProvider publicRouteProvider */
    public function test_public_route_has_no_customer_or_admin_middleware(string $name): void
    {
        $route = RouteFacade::getRoutes()->getByName($name);

        $this->assertInstanceOf(Route::class, $route);
        $middleware = $route->gatherMiddleware();

        $this->assertFalse(
            in_array('admin', $middleware, true),
            "Public route {$name} unexpectedly requires admin middleware."
        );
        $this->assertFalse(
            in_array('auth', $middleware, true) || array_filter(
                $middleware,
                static fn (string $value): bool => str_ends_with($value, '\\Authenticate')
            ),
            "Public route {$name} unexpectedly requires authentication."
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicRouteProvider(): array
    {
        return [
            'home' => ['home'],
            'login form' => ['login'],
            'register form' => ['register.form'],
            'sitemap' => ['sitemap'],
            'products index' => ['products.index'],
            'products show' => ['products.show'],
            'blog index' => ['blog.index'],
            'blog show' => ['blog.show'],
        ];
    }
}
