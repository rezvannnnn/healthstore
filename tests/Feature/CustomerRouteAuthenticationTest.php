<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRouteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_order_show(): void
    {
        $response = $this->get('/orders/ORD-NOT-LOGGED-IN');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_order_cancellation(): void
    {
        $response = $this->post('/orders/ORD-NOT-LOGGED-IN/cancel');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_order_payment(): void
    {
        $response = $this->post('/orders/ORD-NOT-LOGGED-IN/payment');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_checkout(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/login');
    }
}
