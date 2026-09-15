<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProtectedRouteMatrixTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'checkout page' => ['GET', '/checkout'],
            'checkout confirm' => ['POST', '/checkout/confirm'],
            'checkout reject' => ['POST', '/checkout/reject'],
            'order detail' => ['GET', '/orders/TEST-1'],
            'order cancel' => ['POST', '/orders/TEST-1/cancel'],
            'order payment start' => ['POST', '/orders/TEST-1/payment'],
            'cart page' => ['GET', '/cart'],
            'cart item create' => ['POST', '/cart/items'],
            'cart item update' => ['PUT', '/cart/1/items/1'],
            'cart item delete' => ['DELETE', '/cart/1/items/1'],
            'account orders' => ['GET', '/account/orders'],
            'account addresses' => ['GET', '/account/addresses'],
            'account address create' => ['POST', '/account/addresses'],
            'account address update' => ['PUT', '/account/addresses/1'],
            'account address delete' => ['DELETE', '/account/addresses/1'],
            'account profile' => ['GET', '/account/profile'],
            'account profile update' => ['PUT', '/account/profile'],
            'admin dashboard' => ['GET', '/admin'],
            'admin products' => ['GET', '/admin/products'],
            'admin product create' => ['GET', '/admin/products/create'],
            'admin product store' => ['POST', '/admin/products'],
            'admin product edit' => ['GET', '/admin/products/1/edit'],
            'admin product update' => ['PUT', '/admin/products/1'],
            'admin categories' => ['GET', '/admin/categories'],
            'admin category create' => ['GET', '/admin/categories/create'],
            'admin category store' => ['POST', '/admin/categories'],
            'admin category edit' => ['GET', '/admin/categories/1/edit'],
            'admin category update' => ['PUT', '/admin/categories/1'],
            'admin brands' => ['GET', '/admin/brands'],
            'admin brand create' => ['GET', '/admin/brands/create'],
            'admin brand store' => ['POST', '/admin/brands'],
            'admin brand edit' => ['GET', '/admin/brands/1/edit'],
            'admin brand update' => ['PUT', '/admin/brands/1'],
            'admin inventory' => ['GET', '/admin/inventory'],
            'admin inventory store' => ['POST', '/admin/inventory'],
            'admin inventory adjust' => ['POST', '/admin/inventory/1/adjust'],
            'admin inventory movements' => ['GET', '/admin/inventory/1/movements'],
            'admin orders' => ['GET', '/admin/orders'],
            'admin order detail' => ['GET', '/admin/orders/1'],
            'admin order status' => ['POST', '/admin/orders/1/status'],
            'admin payments' => ['GET', '/admin/payments'],
            'admin payment detail' => ['GET', '/admin/payments/1'],
            'admin customers' => ['GET', '/admin/customers'],
            'admin customer detail' => ['GET', '/admin/customers/1'],
            'admin coupons' => ['GET', '/admin/coupons'],
            'admin coupon store' => ['POST', '/admin/coupons'],
            'admin coupon update' => ['PUT', '/admin/coupons/1'],
            'admin coupon delete' => ['DELETE', '/admin/coupons/1'],
            'admin settings' => ['GET', '/admin/settings'],
            'admin settings update' => ['PUT', '/admin/settings'],
            'admin reports' => ['GET', '/admin/reports'],
            'admin articles' => ['GET', '/admin/articles'],
            'admin article create' => ['GET', '/admin/articles/create'],
            'admin article store' => ['POST', '/admin/articles'],
            'admin article edit' => ['GET', '/admin/articles/1/edit'],
            'admin article update' => ['PUT', '/admin/articles/1'],
            'admin article delete' => ['DELETE', '/admin/articles/1'],
        ];
    }

    /**
     * @dataProvider protectedRoutes
     */
    public function test_guest_is_redirected_from_protected_route(string $method, string $uri): void
    {
        $response = $this->call($method, $uri);

        $response->assertRedirect('/login');
    }
}
