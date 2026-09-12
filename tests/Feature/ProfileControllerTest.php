<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->get('/account/profile');

        $response->assertRedirect();
    }

    public function test_authenticated_customer_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->get(
            '/account/profile'
        );

        $response->assertStatus(200);

        $response->assertInertia(function ($page) use ($user) {
            $page
                ->component('Account/Profile/Index')
                ->where('user.id', $user->id)
                ->where('user.name', 'Ali Ahmadi')
                ->where('user.email', 'ali@example.com')
                ->where('user.phone', '09121234567');
        });
    }

    public function test_authenticated_customer_can_update_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'New Name',
                'email' => 'old@example.com',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'old@example.com',
        ]);
    }

    public function test_authenticated_customer_can_update_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'old@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi',
                'email' => 'new@example.com',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_email_is_optional_when_updating_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi Updated',
                'email' => null,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ali Ahmadi Updated',
            'email' => null,
        ]);
    }

    public function test_name_is_required_when_updating_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->from(
            '/account/profile'
        )->actingAs($user)->put(
            '/account/profile',
            [
                'email' => 'new@example.com',
            ]
        );

        $response->assertSessionHasErrors([
            'name',
        ]);
    }

    public function test_email_must_be_valid_when_provided(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->from(
            '/account/profile'
        )->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi',
                'email' => 'not-an-email',
            ]
        );

        $response->assertSessionHasErrors([
            'email',
        ]);
    }

    public function test_email_must_be_unique_when_changed(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '09121111111',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $user = User::factory()->create([
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->from(
            '/account/profile'
        )->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi',
                'email' => 'existing@example.com',
            ]
        );

        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'ali@example.com',
        ]);
    }

    public function test_customer_can_keep_their_existing_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi Updated',
                'email' => 'ali@example.com',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ali Ahmadi Updated',
            'email' => 'ali@example.com',
        ]);
    }

    public function test_profile_update_cannot_change_phone_number(): void
    {
        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Ahmadi',
                'email' => 'ali@example.com',
                'phone' => '09129876543',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '09121234567',
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'phone' => '09129876543',
        ]);
    }

    public function test_profile_update_does_not_change_phone_verification_status(): void
    {
        $verifiedAt = now()->subDay();

        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'phone_verified_at' => $verifiedAt,
            'password' => null,
        ]);

        $response = $this->actingAs($user)->put(
            '/account/profile',
            [
                'name' => 'Ali Updated',
                'email' => 'updated@example.com',
                'phone' => '09129876543',
                'phone_verified_at' => null,
            ]
        );

        $response->assertRedirect();

        $user->refresh();

        $this->assertEquals(
            '09121234567',
            $user->phone
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertEquals(
            'Ali Updated',
            $user->name
        );

        $this->assertEquals(
            'updated@example.com',
            $user->email
        );
    }
}
