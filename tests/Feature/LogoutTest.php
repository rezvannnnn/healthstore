<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_logout(): void
    {
        $user = User::factory()->create([
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();

        $response->assertRedirect();
    }

    public function test_authenticated_customer_session_is_invalidated_after_logout(): void
    {
        $user = User::factory()->create([
            'phone' => '09121234567',
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $this->actingAs($user);

        $oldSessionId = $this->app['session']->getId();

        $response = $this->post('/logout');

        $newSessionId = $this->app['session']->getId();

        $this->assertGuest();

        $this->assertNotEquals(
            $oldSessionId,
            $newSessionId
        );

        $response->assertRedirect();
    }

    public function test_guest_cannot_use_logout_to_authenticate(): void
    {
        $this->assertGuest();

        $response = $this->post('/logout');

        $this->assertGuest();

        $response->assertRedirect();
    }
}
