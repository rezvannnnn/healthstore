<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate their credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ZarinPal
    |--------------------------------------------------------------------------
    */

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),

        'sandbox' => (bool) env(
            'ZARINPAL_SANDBOX',
            true
        ),

        'callback_url' => env(
            'ZARINPAL_CALLBACK_URL',
            '/payment/zarinpal/callback'
        ),

        /*
         * Keep gateway endpoints configurable so the payment module
         * is not coupled to a single environment.
         */
        'request_endpoint' => env(
            'ZARINPAL_REQUEST_ENDPOINT',
            'https://api.zarinpal.com/pg/v4/payment/request.json'
        ),

        'verify_endpoint' => env(
            'ZARINPAL_VERIFY_ENDPOINT',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        ),

        'payment_base_url' => env(
            'ZARINPAL_PAYMENT_BASE_URL',
            'https://www.zarinpal.com'
        ),
    ],

];
