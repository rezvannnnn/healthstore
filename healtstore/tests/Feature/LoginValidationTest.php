<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_send_otp_returns_validation_error_for_invalid_phone(): void
    {
        $response = $this->from('/login')->post('/login/send-otp', [
            'phone' => 'invalid-phone',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['phone']);
    }

    public function test_login_returns_validation_error_for_invalid_phone(): void
    {
        $response = $this->from('/login')->post('/login', [
            'phone' => 'invalid-phone',
            'code' => '123456',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['phone']);
    }
}
