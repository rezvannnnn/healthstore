<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutCouponTest extends TestCase
{
    public function test_checkout_requires_authentication(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect();
    }
}
