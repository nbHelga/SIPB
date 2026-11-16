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

    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'selforder' => [
        // Waktu produksi: 30 menit. Untuk testing gunakan 3 menit.
        // 'payment_window_minutes' => 30,
        'payment_window_minutes' => (int) env('SELFORDER_PAYMENT_WINDOW_MINUTES', 3), // #live ganti default ke 30 jika live/produksi
    ],

    'reservation' => [
        // TEST: 3 menit. Produksi: atur RESERVATION_PAYMENT_WINDOW_MINUTES=60.
        'payment_window_minutes' => (int) env('RESERVATION_PAYMENT_WINDOW_MINUTES', 3), // #live ganti default ke 60 (atau 30) jika live/produksi
        'late_grace_minutes' => (int) env('RESERVATION_LATE_GRACE_MINUTES', 30),
    ],

    // 'selforder_force_open' => env('SELFORDER_FORCE_OPEN', false),

    // FIX SECURITY-04: pindahkan credential Fonnte dari env() langsung ke config
    'fonnte' => [
        'api_key' => env('FONNTE_API_KEY'),
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
        'phone'   => env('FONNTE_PHONE'),
    ],

];
