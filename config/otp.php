<?php

return [
    'master_code' => [
        'enabled' => env('OTP_MASTER_CODE_ENABLED', false),
        'code' => env('OTP_MASTER_CODE', '123456'),
    ],

    'length' => env('OTP_LENGTH', 5),
    'expiry_seconds' => env('OTP_EXPIRY_SECONDS', 120),
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
    'max_resends' => env('OTP_MAX_RESENDS', 3),
    'resend_cooldown_seconds' => env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    'syriatel' => [
        'enabled' => env('OTP_SYRIATEL_ENABLED', false),
        'username' => env('SYRIATEL_OTP_USERNAME'),
        'password' => env('SYRIATEL_OTP_PASSWORD'),
        'sender' => env('SYRIATEL_OTP_SENDER', 'DaqEhjezly'),
        'api_url' => env('SYRIATEL_OTP_API_URL', 'https://api.syriatel.sy/sms'),
    ],

    'mtn' => [
        'enabled' => env('OTP_MTN_ENABLED', false),
        'username' => env('MTN_OTP_USERNAME'),
        'password' => env('MTN_OTP_PASSWORD'),
        'sender' => env('MTN_OTP_SENDER', 'DaqEhjezly'),
        'api_url' => env('MTN_OTP_API_URL', 'https://api.mtn.sy/sms'),
    ],

    'channel_priority' => [
        'whatsapp',
        'syriatel_sms',
        'mtn_sms',
    ],
];
