<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_checkout(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect();
    }

    public function test_customer_cannot_submit_another_customers_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $address = Address::create([
            'user_id' => $otherUser->id,
            'title' => 'خانه',
            'recipient_name' => 'Other User',
            'phone' => '09121234567',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'Other address',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->post('/checkout/confirm', [
            'address_id' => $address->id,
        ]);

        $response->assertRedirect('/checkout');
        $response->assertSessionHasErrors('address_id');
    }

    public function test_customer_cannot_submit_a_missing_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/checkout/confirm', [
            'address_id' => 999999,
        ]);

        $response->assertRedirect('/checkout');
        $response->assertSessionHasErrors('address_id');
    }
}
