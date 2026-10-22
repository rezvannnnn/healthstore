<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_addresses(): void
    {
        $response = $this->get('/account/addresses');

        $response->assertRedirect();
    }

    public function test_authenticated_customer_can_view_own_addresses(): void
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'تبریز، خیابان نمونه، پلاک 10',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get('/account/addresses');

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use ($address, $user) {
            $page
                ->component('Account/Addresses/Index')
                ->has('addresses', 1)
                ->where('addresses.0.id', $address->id)
                ->where('addresses.0.user_id', $user->id)
                ->where('addresses.0.title', 'خانه')
                ->where('addresses.0.recipient_name', 'Ali Ahmadi')
                ->where('addresses.0.phone', '09121234567')
                ->where('addresses.0.is_default', true);
        });
    }

    public function test_customer_cannot_view_another_customers_addresses(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Address::create([
            'user_id' => $owner->id,
            'title' => 'خانه',
            'recipient_name' => 'Owner User',
            'phone' => '09121234567',
            'province' => null,
            'city' => null,
            'address' => 'Owner address',
            'postal_code' => null,
            'is_default' => true,
        ]);

        $response = $this->actingAs($otherUser)->get(
            '/account/addresses'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page
                ->component('Account/Addresses/Index')
                ->has('addresses', 0);
        });
    }

    public function test_authenticated_customer_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            '/account/addresses',
            [
                'title' => 'خانه',
                'recipient_name' => 'Ali Ahmadi',
                'phone' => '09121234567',
                'province' => 'آذربایجان شرقی',
                'city' => 'تبریز',
                'address' => 'تبریز، خیابان نمونه، پلاک 10',
                'postal_code' => '1234567890',
                'is_default' => true,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'تبریز، خیابان نمونه، پلاک 10',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);
    }

    public function test_customer_cannot_create_address_for_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->post(
            '/account/addresses',
            [
                'user_id' => $otherUser->id,
                'title' => 'خانه',
                'recipient_name' => 'Other User',
                'phone' => '09121111111',
                'province' => 'تهران',
                'city' => 'تهران',
                'address' => 'Other address',
                'postal_code' => '1111111111',
                'is_default' => false,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'recipient_name' => 'Other User',
        ]);

        $this->assertDatabaseMissing('addresses', [
            'user_id' => $otherUser->id,
            'recipient_name' => 'Other User',
        ]);
    }

    public function test_address_requires_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->from(
            '/account/addresses/create'
        )->actingAs($user)->post(
            '/account/addresses',
            []
        );

        $response->assertSessionHasErrors([
            'recipient_name',
            'phone',
            'address',
        ]);
    }

    public function test_authenticated_customer_can_update_own_address(): void
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Old Name',
            'phone' => '09121234567',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'Old address',
            'postal_code' => '1111111111',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/addresses/'.$address->id,
            [
                'title' => 'محل کار',
                'recipient_name' => 'New Name',
                'phone' => '09129876543',
                'province' => 'آذربایجان شرقی',
                'city' => 'تبریز',
                'address' => 'New address',
                'postal_code' => '2222222222',
                'is_default' => false,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $user->id,
            'title' => 'محل کار',
            'recipient_name' => 'New Name',
            'phone' => '09129876543',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'New address',
            'postal_code' => '2222222222',
            'is_default' => false,
        ]);
    }

    public function test_customer_cannot_update_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $address = Address::create([
            'user_id' => $owner->id,
            'title' => 'خانه',
            'recipient_name' => 'Owner User',
            'phone' => '09121234567',
            'province' => null,
            'city' => null,
            'address' => 'Owner address',
            'postal_code' => null,
            'is_default' => true,
        ]);

        $response = $this->actingAs($otherUser)->put(
            '/account/addresses/'.$address->id,
            [
                'title' => 'تغییر یافته',
                'recipient_name' => 'Hacker',
                'phone' => '09129999999',
                'province' => 'تهران',
                'city' => 'تهران',
                'address' => 'Changed address',
                'postal_code' => '9999999999',
                'is_default' => false,
            ]
        );

        $response->assertStatus(404);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $owner->id,
            'recipient_name' => 'Owner User',
        ]);
    }

    public function test_authenticated_customer_can_delete_own_address(): void
    {
        $user = User::factory()->create();

        $address = Address::create([
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'province' => null,
            'city' => null,
            'address' => 'Address to delete',
            'postal_code' => null,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->delete(
            '/account/addresses/'.$address->id
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_customer_cannot_delete_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $address = Address::create([
            'user_id' => $owner->id,
            'title' => 'خانه',
            'recipient_name' => 'Owner User',
            'phone' => '09121234567',
            'province' => null,
            'city' => null,
            'address' => 'Owner address',
            'postal_code' => null,
            'is_default' => true,
        ]);

        $response = $this->actingAs($otherUser)->delete(
            '/account/addresses/'.$address->id
        );

        $response->assertStatus(404);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_deleting_default_address_promotes_oldest_remaining_address(): void
    {
        $user = User::factory()->create();

        $firstAddress = Address::create([
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'address' => 'First address',
            'is_default' => false,
        ]);

        $defaultAddress = Address::create([
            'user_id' => $user->id,
            'title' => 'محل کار',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'address' => 'Default address',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->delete(
            '/account/addresses/'.$defaultAddress->id
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('addresses', [
            'id' => $defaultAddress->id,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $firstAddress->id,
            'is_default' => true,
        ]);
    }

    public function test_customer_can_make_an_address_default(): void
    {
        $user = User::factory()->create();

        $firstAddress = Address::create([
            'user_id' => $user->id,
            'title' => 'خانه',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'First address',
            'postal_code' => '1111111111',
            'is_default' => true,
        ]);

        $secondAddress = Address::create([
            'user_id' => $user->id,
            'title' => 'محل کار',
            'recipient_name' => 'Ali Ahmadi',
            'phone' => '09121234567',
            'province' => 'آذربایجان شرقی',
            'city' => 'تبریز',
            'address' => 'Second address',
            'postal_code' => '2222222222',
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/addresses/'.$secondAddress->id,
            [
                'title' => 'محل کار',
                'recipient_name' => 'Ali Ahmadi',
                'phone' => '09121234567',
                'province' => 'آذربایجان شرقی',
                'city' => 'تبریز',
                'address' => 'Second address',
                'postal_code' => '2222222222',
                'is_default' => true,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $firstAddress->id,
            'is_default' => false,
        ]);
    }
}
