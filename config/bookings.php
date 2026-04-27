<?php

return [
    'reschedule' => [
        'min_minutes_before' => (int) env('BOOKING_RESCHEDULE_MIN_MINUTES', 60),
        'max_per_booking' => (int) env('BOOKING_RESCHEDULE_MAX', 2),
        'max_days_ahead' => (int) env('BOOKING_RESCHEDULE_MAX_DAYS', 30),
    ],

    'refund' => [
        'auto_approve_threshold' => (int) env('REFUND_AUTO_APPROVE_THRESHOLD', 50000),
        'cancellation_policy' => [
            48 => 100,
            24 => 75,
            6 => 50,
            1 => 25,
            0 => 0,
        ],
    ],

    'moderation' => [
        'auto_flag_venue_threshold' => (int) env('VENUE_AUTO_FLAG_REPORTS', 5),
        'auto_hide_review_threshold' => (int) env('REVIEW_AUTO_HIDE_REPORTS', 3),
    ],
];
