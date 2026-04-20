<?php

namespace App\Jobs;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupExpiredBookingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $expiredCount = Booking::where('status', 'pending_payment')
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Payment timeout - auto-cancelled',
            ]);

        Log::info('Expired bookings cleaned up', ['count' => $expiredCount]);
    }
}
