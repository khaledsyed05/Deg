<?php

return [

    'cache' => [
        'today_matches_ttl' => (int) env('FOOTBALL_CACHE_TODAY', 300),
        'upcoming_matches_ttl' => (int) env('FOOTBALL_CACHE_UPCOMING', 1800),
        'standings_ttl' => (int) env('FOOTBALL_CACHE_STANDINGS', 3600),
        'teams_ttl' => (int) env('FOOTBALL_CACHE_TEAMS', 86400),
        'live_score_ttl' => (int) env('FOOTBALL_CACHE_LIVE', 30),
    ],

    'polling' => [
        'peak_hours_start' => env('FOOTBALL_PEAK_START', '15:00'),
        'peak_hours_end' => env('FOOTBALL_PEAK_END', '23:00'),
        'peak_interval_minutes' => (int) env('FOOTBALL_PEAK_INTERVAL', 1),
        'offpeak_interval_minutes' => (int) env('FOOTBALL_OFFPEAK_INTERVAL', 5),
    ],

    'featured_leagues' => [
        'PL', 'PD', 'CL', 'BL1', 'SA', 'FL1', 'DED', 'PPL', 'BSA', 'ELC',
    ],
];
