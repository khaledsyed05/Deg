<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:send-reminders')]
#[Description('Send reminders for bookings starting in 2 hours')]
class SendBookingRemindersCommand extends Command
{
    public function handle(): int
    {
        $twoHoursFromNow = Carbon::now()->addHours(2);

        $bookings = Booking::where('status', 'confirmed')
            ->whereBetween('starts_at', [
                $twoHoursFromNow->copy()->subMinutes(5),
                $twoHoursFromNow->copy()->addMinutes(5),
            ])
            ->get();

        foreach ($bookings as $booking) {
            SendBookingReminderJob::dispatch($booking);
        }

        $this->info("Dispatched {$bookings->count()} reminder jobs");

        return self::SUCCESS;
    }
}
