<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
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

    'selforder' => [
        // TEST: 3 menit. Produksi: atur SELFORDER_PAYMENT_WINDOW_MINUTES=120.
        'payment_window_minutes' => (int) env('SELFORDER_PAYMENT_WINDOW_MINUTES', 3),
    ],

    'reservation' => [
        // TEST: 3 menit. Produksi: atur RESERVATION_PAYMENT_WINDOW_MINUTES=60.
        'payment_window_minutes' => (int) env('RESERVATION_PAYMENT_WINDOW_MINUTES', 3),
    ],

    // 'selforder_force_open' => env('SELFORDER_FORCE_OPEN', false),

];
