<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

class ProcessRecurringBookings extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Process recurring bookings — create instances, charge, expire end-dated subscriptions';

    public function handle(SubscriptionService $service): int
    {
        $this->info('Processing recurring bookings...');

        $processed = $service->processDueSubscriptions();

        $this->info("Processed {$processed} subscription(s).");

        $expired = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->toDateString())
            ->update(['status' => 'expired']);

        if ($expired > 0) {
            $this->info("Expired {$expired} subscription(s).");
        }

        return self::SUCCESS;
    }
}
