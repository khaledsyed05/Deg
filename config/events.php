<?php

return [
    'cancellation' => [
        'min_hours_before' => env('EVENT_CANCEL_MIN_HOURS', 24),
        'auto_refund' => true,
    ],

    'reminders' => [
        'enabled' => true,
        'send_at_hours' => [24, 1],
    ],

    'capacity' => [
        'overbook_allowed' => false,
        'reservation_timeout_minutes' => 15,
    ],
];
