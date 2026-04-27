<?php

return [
    'min_topup' => (int) env('WALLET_MIN_TOPUP', 5_000),
    'max_topup_per_transaction' => (int) env('WALLET_MAX_TOPUP_PER_TX', 1_000_000),
    'daily_topup_cap' => (int) env('WALLET_DAILY_TOPUP_CAP', 10_000_000),

    /*
     * Bonus tiers — first matching threshold wins. Order high → low.
     */
    'bonus_tiers' => [
        ['threshold' => 500_000, 'percentage' => 10],
        ['threshold' => 200_000, 'percentage' => 5],
        ['threshold' => 50_000, 'percentage' => 2],
    ],

    'bonus_credits_expire_after_days' => (int) env('WALLET_BONUS_EXPIRE_DAYS', 60),
];
