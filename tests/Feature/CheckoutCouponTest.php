<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutCouponTest extends TestCase
{
    public function test_checkout_page_is_protected(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect();
    }
}
