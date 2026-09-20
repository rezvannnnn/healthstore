<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_application_responses_include_baseline_security_headers(): void
    {
        $response = $this->get('/');

        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_secure_responses_include_hsts_header(): void
    {
        $this->get('/')->assertHeader('Strict-Transport-Security');
    }
}
