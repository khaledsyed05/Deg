<?php

return [
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'mtn_cash'),

    'gateways' => [
        'mtn_cash' => [
            'enabled' => env('PAYMENT_MTN_ENABLED', true),
            'class' => \App\Services\Payment\Gateways\MtnCashGateway::class,
        ],
        'syriatel_cash' => [
            'enabled' => env('PAYMENT_SYRIATEL_ENABLED', true),
            'class' => \App\Services\Payment\Gateways\SyriatelCashGateway::class,
        ],
        'fatora' => [
            'enabled' => env('PAYMENT_FATORA_ENABLED', true),
            'class' => \App\Services\Payment\Gateways\FatoraGateway::class,
        ],
        'sama_pay' => [
            'enabled' => env('PAYMENT_SAMAPAY_ENABLED', false),
            'class' => \App\Services\Payment\Gateways\SamaPayGateway::class,
        ],
    ],

    'mtn' => [
        'base_url'       => env('PAYMENT_MTN_BASE_URL', 'https://sandbox.mtn.sy'),
        'api_key'        => env('PAYMENT_MTN_API_KEY'),
        'merchant_id'    => env('PAYMENT_MTN_MERCHANT_ID'),
        'webhook_secret' => env('PAYMENT_MTN_WEBHOOK_SECRET'),
    ],

    'syriatel' => [
        'base_url'       => env('PAYMENT_SYRIATEL_BASE_URL', 'https://sandbox.syriatel.sy'),
        'api_key'        => env('PAYMENT_SYRIATEL_API_KEY'),
        'merchant_code'  => env('PAYMENT_SYRIATEL_MERCHANT_CODE'),
        'webhook_secret' => env('PAYMENT_SYRIATEL_WEBHOOK_SECRET'),
    ],

    'fatora' => [
        'base_url'       => env('PAYMENT_FATORA_BASE_URL', 'https://api.fatora.io'),
        'api_key'        => env('PAYMENT_FATORA_API_KEY'),
        'webhook_secret' => env('PAYMENT_FATORA_WEBHOOK_SECRET'),
    ],

    'samapay' => [
        'base_url'       => env('PAYMENT_SAMAPAY_BASE_URL'),
        'api_key'        => env('PAYMENT_SAMAPAY_API_KEY'),
        'webhook_secret' => env('PAYMENT_SAMAPAY_WEBHOOK_SECRET'),
    ],
];
