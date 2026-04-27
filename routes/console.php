<?php

use App\Jobs\CleanupExpiredBookingsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send booking reminders every hour
Schedule::command('bookings:send-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Cleanup expired bookings every 10 minutes
Schedule::job(new CleanupExpiredBookingsJob)
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Generate settlements on the 1st of every month at 01:00 AM
Schedule::command('settlements:generate')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->onOneServer();

// Horizon snapshot (keep metrics)
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->onOneServer();

// Process recurring bookings daily at 00:15
Schedule::command('subscriptions:process')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->onOneServer();

// Expire promotional wallet credits daily at 01:00
Schedule::command('wallet:expire-credits')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

// Phase 12 — Football
Schedule::command('football:sync')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('football:daily-summary')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('football:reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('football:poll-live')
    ->everyMinute()
    ->between(config('football.polling.peak_hours_start', '15:00'), config('football.polling.peak_hours_end', '23:00'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('football:poll-live')
    ->everyFiveMinutes()
    ->unlessBetween(config('football.polling.peak_hours_start', '15:00'), config('football.polling.peak_hours_end', '23:00'))
    ->withoutOverlapping()
    ->onOneServer();

// Phase 14 — Geography
Schedule::command('geography:update-counts')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Phase 16 — Event reminders (24h + 1h before each event)
Schedule::command('events:send-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();
