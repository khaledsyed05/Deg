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

    'whatsapp' => [
        'base_url' => env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'),
        'api_key' => env('WHATSAPP_SERVICE_API_KEY', ''),
    ],

    'football_data' => [
        'api_key' => env('FOOTBALL_DATA_API_KEY'),
        'base_url' => env('FOOTBALL_DATA_BASE_URL', 'https://api.football-data.org/v4'),
    ],

    'api_sports' => [
        'key_1' => env('API_SPORTS_KEY_1'),
        'key_2' => env('API_SPORTS_KEY_2'),
        'key_3' => env('API_SPORTS_KEY_3'),
        'base_url' => env('API_SPORTS_BASE_URL', 'https://v3.football.api-sports.io'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials_path' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
        'service_account' => env('FIREBASE_SERVICE_ACCOUNT'),
    ],

    // Sprint 8 B3 — webhook signature verification per provider.
    // verify_signatures defaults to false in dev/sandbox so providers
    // that don't sign yet can still call back without 401. Production
    // sets PAYMENT_VERIFY_SIGNATURES=true.
    'syriatel' => [
        'webhook_secret' => env('SYRIATEL_WEBHOOK_SECRET'),
        'webhook_header' => env('SYRIATEL_WEBHOOK_HEADER', 'X-Signature'),
        'verify_signatures' => env('PAYMENT_VERIFY_SIGNATURES', false),
    ],

    'mtn' => [
        'webhook_secret' => env('MTN_WEBHOOK_SECRET'),
        'webhook_header' => env('MTN_WEBHOOK_HEADER', 'X-Signature'),
        'verify_signatures' => env('PAYMENT_VERIFY_SIGNATURES', false),
    ],

    'apns' => [
        'enabled' => env('APNS_ENABLED', false),
        'use_sandbox' => env('APNS_USE_SANDBOX', false),
        'auth_key_path' => env('APNS_AUTH_KEY_PATH'),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
        'bundle_id' => env('APNS_BUNDLE_ID', 'com.example.degEhjizli'),
    ],

];
