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
