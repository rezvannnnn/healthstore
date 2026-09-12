<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Services\OtpService;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

       
        /*
         * Use the fake SMS provider so no real SMS is ever sent
         * during automated tests.
         */
        $this->app->singleton(
            SmsProviderInterface::class,
            FakeSmsProvider::class
        );
    }

    public function test_otp_can_be_generated_and_sent_for_a_phone_number(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $this->assertArrayHasKey(
            'verification',
            $result
        );

        $verification = $result['verification'];

        $this->assertInstanceOf(
            OtpVerification::class,
            $verification
        );

        $this->assertEquals(
            '09121234567',
            $verification->phone
        );

        $this->assertEquals(
            0,
            $verification->attempts
        );

        $this->assertNull(
            $verification->verified_at
        );

        /*
         * The OTP must be stored as a hash.
         */
        $this->assertNotEmpty(
            $verification->code_hash
        );

        /*
         * The plaintext OTP must not be returned by the service.
         */
        $this->assertArrayNotHasKey(
            'code',
            $result
        );

        /*
         * Verify that the SMS provider received exactly one message.
         */
        $provider = app(
            SmsProviderInterface::class
        );

        $this->assertInstanceOf(
            FakeSmsProvider::class,
            $provider
        );

        $message = $provider->lastMessage();

        $this->assertNotNull(
            $message
        );

        $this->assertEquals(
            '09121234567',
            $message['phone']
        );

        /*
         * The message should contain a six-digit OTP.
         */
        $this->assertMatchesRegularExpression(
            '/\b\d{6}\b/',
            $message['message']
        );

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

        /*
         * Verify that the code sent through SMS matches
         * the hash stored in the database.
         */
        $this->assertTrue(
            Hash::check(
                $code,
                $verification->code_hash
            )
        );
    }

    public function test_generated_otp_expires_after_configured_time(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $verification = $result['verification'];

        $this->assertTrue(
            $verification->isValid()
        );

        $verification->update([
            'expires_at' => now()->subSecond(),
        ]);

        $verification->refresh();

        $this->assertFalse(
            $verification->isValid()
        );
    }

    public function test_correct_otp_can_be_verified(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $verification = $result['verification'];

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull(
            $message
        );

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

        $verified = $service->verify(
            '09121234567',
            $code
        );

        $this->assertTrue(
            $verified
        );

        $verification->refresh();

        $this->assertNotNull(
            $verification->verified_at
        );

        $this->assertTrue(
            $verification->isVerified()
        );
    }

    public function test_incorrect_otp_increments_attempts(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $verification = $result['verification'];

        $verified = $service->verify(
            '09121234567',
            '000000'
        );

        $this->assertFalse(
            $verified
        );

        $verification->refresh();

        $this->assertEquals(
            1,
            $verification->attempts
        );

        $this->assertNull(
            $verification->verified_at
        );
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $verification = $result['verification'];

        $verification->update([
            'expires_at' => now()->subSecond(),
        ]);

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull(
            $message
        );

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

        $verified = $service->verify(
            '09121234567',
            $code
        );

        $this->assertFalse(
            $verified
        );

        $verification->refresh();

        $this->assertNull(
            $verification->verified_at
        );
    }

    public function test_otp_cannot_be_verified_after_maximum_attempts(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $verification = $result['verification'];

        $verification->update([
            'attempts' => 5,
        ]);

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull(
            $message
        );

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

        $verified = $service->verify(
            '09121234567',
            $code
        );

        $this->assertFalse(
            $verified
        );

        $verification->refresh();

        $this->assertNull(
            $verification->verified_at
        );
    }

    public function test_verified_otp_cannot_be_verified_again(): void
    {
        $service = app(OtpService::class);

        $result = $service->send(
            '09121234567'
        );

        $provider = app(
            SmsProviderInterface::class
        );

        $message = $provider->lastMessage();

        $this->assertNotNull(
            $message
        );

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

        $service->verify(
            '09121234567',
            $code
        );

        $verifiedAgain = $service->verify(
            '09121234567',
            $code
        );

        $this->assertFalse(
            $verifiedAgain
        );
    }

   public function test_new_otp_replaces_the_previous_active_otp_for_same_phone(): void
{
    $service = app(OtpService::class);

    $first = $service->send(
        '09121234567'
    );

    $provider = app(
        SmsProviderInterface::class
    );

    $firstMessage = $provider->lastMessage();

    $this->assertNotNull(
        $firstMessage
    );

    preg_match(
        '/\b(\d{6})\b/',
        $firstMessage['message'],
        $firstMatches
    );

    $this->assertArrayHasKey(
        1,
        $firstMatches
    );

    $firstCode = $firstMatches[1];

    /*
     * Move the first OTP's creation time back beyond the cooldown.
     * This avoids relying on the test clock implementation.
     */
$first['verification']->created_at = now()->subSeconds(
    config('otp.resend_cooldown_seconds') + 10
);

$first['verification']->save();

$first['verification']->refresh();

    $second = $service->send(
        '09121234567'
    );

    $secondMessage = $provider->lastMessage();

    $this->assertNotNull(
        $secondMessage
    );

    preg_match(
        '/\b(\d{6})\b/',
        $secondMessage['message'],
        $secondMatches
    );

    $this->assertArrayHasKey(
        1,
        $secondMatches
    );

    $secondCode = $secondMatches[1];

    $this->assertNotEquals(
        $first['verification']->id,
        $second['verification']->id
    );

    $this->assertDatabaseCount(
        'otp_verifications',
        2
    );

    /*
     * The previous OTP must no longer be usable.
     */
    $this->assertFalse(
        $service->verify(
            '09121234567',
            $firstCode
        )
    );

    /*
     * The newest OTP must work.
     */
    $this->assertTrue(
        $service->verify(
            '09121234567',
            $secondCode
        )
    );
}
    public function test_new_otp_cannot_be_requested_before_resend_cooldown_expires(): void
{
    $service = app(OtpService::class);

    $service->send(
        '09121234567'
    );

    $this->expectException(\RuntimeException::class);

    $service->send(
        '09121234567'
    );
}
public function test_otp_requests_are_limited_per_hour(): void
{
    $service = app(OtpService::class);

    $phone = '09121234567';

    for ($i = 0; $i < 5; $i++) {
        $service->send($phone);

        /*
         * Move past the resend cooldown so each request is allowed
         * by the cooldown rule and we can specifically test the
         * hourly request limit.
         */
        $verification = \App\Models\OtpVerification::query()
            ->where('phone', $phone)
            ->latest('id')
            ->firstOrFail();

        $verification->created_at = now()->subSeconds(
            config('otp.resend_cooldown_seconds') + 10
        );

        $verification->save();
    }

    $this->expectException(\RuntimeException::class);

    $service->send($phone);
}
}