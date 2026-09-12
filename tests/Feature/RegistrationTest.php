<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
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

    private function sendRegistrationOtpAndGetCode(
        string $phone
    ): string {
        $response = $this->post(
            '/register/send-otp',
            [
                'phone' => $phone,
            ]
        );

        $response->assertSessionHas(
            'status',
            'کد تأیید ارسال شد.'
        );

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

    private function verifyRegistrationOtp(
        string $phone,
        string $code
    ): void {
        $response = $this->post(
            '/register/verify-otp',
            [
                'phone' => $phone,
                'code' => $code,
            ]
        );

        $response->assertSessionHas(
            'registration_phone',
            '09121234567'
        );

        $response->assertSessionHas(
            'registration_phone_verified',
            true
        );
    }

    public function test_guest_can_register_with_a_verified_phone_number(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->post('/register', [
            'name' => 'Ali Ahmadi',
            'phone' => $phone,
            'email' => null,
        ]);

        $user = User::where(
            'phone',
            $phone
        )->first();

        $this->assertNotNull($user);

        $this->assertEquals(
            'Ali Ahmadi',
            $user->name
        );

        $this->assertEquals(
            $phone,
            $user->phone
        );

        $this->assertNull(
            $user->email
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $response->assertRedirect();
    }

    public function test_email_is_optional_during_registration(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->post('/register', [
            'name' => 'No Email User',
            'phone' => $phone,
        ]);

        $response->assertRedirect();

        $user = User::where(
            'phone',
            $phone
        )->first();

        $this->assertNotNull($user);

        $this->assertNull(
            $user->email
        );

        $this->assertAuthenticatedAs(
            $user
        );
    }

    public function test_registration_send_otp_sends_sms(): void
    {
        $phone = '09121234567';

        $response = $this->post(
            '/register/send-otp',
            [
                'phone' => $phone,
            ]
        );

        $response->assertSessionHas(
            'status',
            'کد تأیید ارسال شد.'
        );

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull($message);

        $this->assertEquals(
            $phone,
            $message['phone']
        );

        $this->assertMatchesRegularExpression(
            '/\b\d{6}\b/',
            $message['message']
        );

        $this->assertDatabaseHas(
            'otp_verifications',
            [
                'phone' => $phone,
            ]
        );
    }

    public function test_registration_send_otp_requires_a_phone(): void
    {
        $response = $this->from(
            '/register'
        )->post(
            '/register/send-otp',
            []
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);
    }

    public function test_registration_send_otp_rejects_invalid_phone(): void
    {
        $response = $this->from(
            '/register'
        )->post(
            '/register/send-otp',
            [
                'phone' => '12345',
            ]
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);
    }

    public function test_registration_verify_otp_requires_phone_and_code(): void
    {
        $response = $this->from(
            '/register'
        )->post(
            '/register/verify-otp',
            []
        );

        $response->assertSessionHasErrors([
            'phone',
            'code',
        ]);
    }

    public function test_registration_verify_otp_succeeds_with_correct_code(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $response = $this->post(
            '/register/verify-otp',
            [
                'phone' => $phone,
                'code' => $code,
            ]
        );

        $response->assertSessionHas(
            'registration_phone',
            $phone
        );

        $response->assertSessionHas(
            'registration_phone_verified',
            true
        );

        $verification = OtpVerification::query()
            ->where('phone', $phone)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull(
            $verification->verified_at
        );
    }

    public function test_registration_verify_otp_fails_with_invalid_code(): void
    {
        $phone = '09121234567';

        $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $response = $this->from(
            '/register'
        )->post(
            '/register/verify-otp',
            [
                'phone' => $phone,
                'code' => '000000',
            ]
        );

        $response->assertSessionHasErrors([
            'code',
        ]);

        $this->assertFalse(
            session('registration_phone_verified', false)
        );
    }

    public function test_registration_requires_a_verified_phone(): void
    {
        $phone = '09121234567';

        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => 'Unverified User',
                'phone' => $phone,
                'email' => null,
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

    public function test_registration_cannot_use_an_unrelated_verified_otp(): void
    {
        $phone = '09121234567';

        $this->post(
            '/register/send-otp',
            [
                'phone' => $phone,
            ]
        );

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

        $code = $matches[1];

        app(
            \App\Services\OtpService::class
        )->verify(
            $phone,
            $code
        );

        /*
         * Deliberately do not create the registration verification
         * session state.
         */
        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => 'Blocked User',
                'phone' => $phone,
                'email' => null,
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

    public function test_name_is_required(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => '',
                'phone' => $phone,
            ]
        );

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertGuest();
    }

    public function test_phone_is_required(): void
    {
        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => 'Test User',
                'email' => null,
            ]
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);

        $this->assertGuest();
    }

    public function test_email_must_be_valid_when_provided(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => 'Test User',
                'phone' => $phone,
                'email' => 'not-an-email',
            ]
        );

        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'phone' => $phone,
            ]
        );
    }

    public function test_email_must_be_unique_when_provided(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '09121111111',
            'phone_verified_at' => now(),
        ]);

        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->from(
            '/register'
        )->post(
            '/register',
            [
                'name' => 'Another User',
                'phone' => $phone,
                'email' => 'existing@example.com',
            ]
        );

        $response->assertSessionHasErrors([
            'email',
        ]);

        $this->assertGuest();

        $this->assertDatabaseCount(
            'users',
            1
        );
    }

   public function test_phone_must_be_unique(): void
{
    User::factory()->create([
        'phone' => '09121234567',
        'phone_verified_at' => now(),
    ]);

    $response = $this->from(
        '/register'
    )->post(
        '/register/send-otp',
        [
            'phone' => '09121234567',
        ]
    );

    $response->assertSessionHasErrors([
        'phone',
    ]);

    $this->assertGuest();

    $this->assertDatabaseCount(
        'users',
        1
    );

    $this->assertDatabaseMissing(
        'otp_verifications',
        [
            'phone' => '09121234567',
        ]
    );
}

    public function test_phone_is_normalized_before_registration(): void
    {
        $phone = '+989121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->post(
            '/register',
            [
                'name' => 'Normalized User',
                'phone' => '00989121234567',
                'email' => null,
            ]
        );

        $response->assertRedirect();

        $user = User::where(
            'phone',
            '09121234567'
        )->first();

        $this->assertNotNull($user);

        $this->assertEquals(
            '09121234567',
            $user->phone
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertAuthenticatedAs(
            $user
        );
    }

    public function test_registration_does_not_require_a_password(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $response = $this->post(
            '/register',
            [
                'name' => 'Passwordless User',
                'phone' => $phone,
                'email' => null,
            ]
        );

        $response->assertRedirect();

        $user = User::where(
            'phone',
            $phone
        )->first();

        $this->assertNotNull($user);

        $this->assertNull(
            $user->password
        );

        $this->assertAuthenticatedAs(
            $user
        );
    }

    public function test_successful_registration_marks_phone_as_verified(): void
    {
        $phone = '09121234567';

        $code = $this->sendRegistrationOtpAndGetCode(
            $phone
        );

        $this->verifyRegistrationOtp(
            $phone,
            $code
        );

        $this->post(
            '/register',
            [
                'name' => 'Verified User',
                'phone' => $phone,
                'email' => 'verified@example.com',
            ]
        );

        $user = User::where(
            'phone',
            $phone
        )->firstOrFail();

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertTrue(
            $user->hasVerifiedPhone()
        );
    }

    public function test_registration_send_otp_rejects_already_registered_phone(): void
    {
        User::factory()->create([
            'phone' => '09121234567',
            'phone_verified_at' => now(),
        ]);

        $response = $this->from(
            '/register'
        )->post(
            '/register/send-otp',
            [
                'phone' => '09121234567',
            ]
        );

        $response->assertSessionHasErrors([
            'phone',
        ]);

        $provider = app(
            SmsProviderInterface::class
        );

        $this->assertNull(
            $provider->lastMessage()
        );
    }
}