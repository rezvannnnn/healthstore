<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutValidationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_confirm_requires_address_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/checkout/confirm')
            ->assertSessionHasErrors('address_id');
    }

    public function test_checkout_confirm_requires_integer_address_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/checkout/confirm', ['address_id' => 'abc'])
            ->assertSessionHasErrors('address_id');
    }

    public function test_checkout_confirm_accepts_coupon_code_at_maximum_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/checkout/confirm', [
            'address_id' => 999999,
            'coupon_code' => str_repeat('A', 64),
        ]);

        $response->assertRedirect('/checkout');
        $response->assertSessionHasErrors('address_id');
        $response->assertSessionMissing('errors.coupon_code');
    }

    public function test_checkout_confirm_rejects_oversized_coupon_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/checkout/confirm', [
                'address_id' => 999999,
                'coupon_code' => str_repeat('A', 65),
            ])
            ->assertSessionHasErrors('coupon_code');
    }

    public function test_checkout_confirm_rejects_non_string_coupon_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/checkout/confirm', [
                'address_id' => 999999,
                'coupon_code' => ['VIP'],
            ])
            ->assertSessionHasErrors('coupon_code');
    }
}
