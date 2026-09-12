<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(
            SmsProviderInterface::class,
            FakeSmsProvider::class
        );
    }

    private function sendOtpAndGetCode(
        string $phone
    ): string {
        $service = app(OtpService::class);

        $service->send($phone);

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull($message);

        preg_match(
            '/\b(\d{6})\b/',
            $message['message'],
            $matches
        );

        $this->assertArrayHasKey(
            1,
            $matches
        );

        return $matches[1];
    }

    public function test_registered_customer_can_login_with_verified_otp(): void
    {
        $phone = '09121234567';

        $user = User::factory()->create([
            'name' => 'Ali Ahmadi',
            'phone' => $phone,
            'email' => null,
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $code = $this->sendOtpAndGetCode($phone);

        $response = $this->post('/login', [
            'phone' => $phone,
            'code' => $code,
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect();
    }

    public function test_login_fails_with_invalid_otp(): void
    {
        $phone = '09121234567';

        User::factory()->create([
            'phone' => $phone,
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $this->sendOtpAndGetCode($phone);

        $response = $this->from('/login')->post(
            '/login',
            [
                'phone' => $phone,
                'code' => '000000',
            ]
        );

        $response->assertSessionHasErrors([
            'code',
        ]);

        $this->assertGuest();
    }

    public function test_unregistered_phone_cannot_login(): void
    {
        $phone = '09121234567';

        /*
         * Send the OTP but do not verify it here.
         * LoginController must verify it during the login request.
         */
        $code = $this->sendOtpAndGetCode($phone);

        $response = $this->from('/login')->post(
            '/login',
            [
                'phone' => $phone,
                'code' => $code,
            ]
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'phone' => $phone,
            ]
        );
    }

    public function test_login_phone_is_normalized(): void
    {
        $storedPhone = '09121234567';

        $user = User::factory()->create([
            'phone' => $storedPhone,
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $code = $this->sendOtpAndGetCode(
            '+989121234567'
        );

        $response = $this->post('/login', [
            'phone' => '00989121234567',
            'code' => $code,
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect();
    }

    public function test_login_requires_phone(): void
    {
        $response = $this->from('/login')->post(
            '/login',
            [
                'code' => '123456',
            ]
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);

        $this->assertGuest();
    }

    public function test_login_requires_code(): void
    {
        $response = $this->from('/login')->post(
            '/login',
            [
                'phone' => '09121234567',
            ]
        );

        $response->assertSessionHasErrors([
            'code',
        ]);

        $this->assertGuest();
    }

    public function test_expired_otp_cannot_login(): void
    {
        $phone = '09121234567';

        User::factory()->create([
            'phone' => $phone,
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $code = $this->sendOtpAndGetCode($phone);

        $verification = OtpVerification::query()
            ->where('phone', $phone)
            ->latest('id')
            ->firstOrFail();

        $verification->update([
            'expires_at' => now()->subSecond(),
        ]);

        $response = $this->from('/login')->post(
            '/login',
            [
                'phone' => $phone,
                'code' => $code,
            ]
        );

        $response->assertSessionHasErrors([
            'code',
        ]);

        $this->assertGuest();
    }

    public function test_successful_login_regenerates_session(): void
    {
        $phone = '09121234567';

        $user = User::factory()->create([
            'phone' => $phone,
            'phone_verified_at' => now(),
            'password' => null,
        ]);

        $code = $this->sendOtpAndGetCode($phone);

        $oldSessionId = $this->app['session']->getId();

        $response = $this->post('/login', [
            'phone' => $phone,
            'code' => $code,
        ]);

        $newSessionId = $this->app['session']->getId();

        $this->assertAuthenticatedAs($user);

        $this->assertNotEquals(
            $oldSessionId,
            $newSessionId
        );

        $response->assertRedirect();
    }
}
