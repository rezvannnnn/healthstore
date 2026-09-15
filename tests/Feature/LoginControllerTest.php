<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_customer_can_login_with_persian_phone_digits(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'phone_verified_at' => now(),
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        $response = $this->post('/login', [
            'phone' => '۰۹۱۲۳۴۵۶۷۸۹',
            'code' => '123456',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('otp_verifications', [
            'phone' => $user->phone,
        ]);
    }

    public function test_invalid_login_otp_is_rejected_and_attempt_is_recorded(): void
    {
        $user = User::factory()->create([
            'phone' => '09129876543',
            'phone_verified_at' => now(),
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'verified_at' => null,
        ]);

        $this->post('/login', [
            'phone' => $user->phone,
            'code' => '123456',
        ])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, OtpVerification::query()->where('phone', $user->phone)->value('attempts'));
    }
}
