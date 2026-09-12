<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */

    'code_length' => (int) env(
        'OTP_CODE_LENGTH',
        6
    ),

    'expires_in_minutes' => (int) env(
        'OTP_EXPIRES_IN_MINUTES',
        5
    ),

    'max_attempts' => (int) env(
        'OTP_MAX_ATTEMPTS',
        5
    ),

    'resend_cooldown_seconds' => (int) env(
        'OTP_RESEND_COOLDOWN_SECONDS',
        60
    ),

    'max_requests_per_hour' => (int) env(
        'OTP_MAX_REQUESTS_PER_HOUR',
        5
    ),

];