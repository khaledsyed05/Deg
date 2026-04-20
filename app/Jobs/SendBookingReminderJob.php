<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public Booking $booking)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        if ($this->booking->status->value !== 'confirmed') {
            return;
        }

        $this->booking->user->notify(
            new BookingReminderNotification($this->booking)
        );
    }
}
