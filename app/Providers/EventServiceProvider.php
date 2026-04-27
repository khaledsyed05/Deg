<?php

namespace App\Providers;

use App\Listeners\LogQueueFailure;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Referral;
use App\Models\Review;
use App\Models\User;
use App\Models\VenuePricingTier;
use App\Models\WalletTransaction;
use App\Observers\BookingObserver;
use App\Observers\ClubObserver;
use App\Observers\ReferralObserver;
use App\Observers\ReviewObserver;
use App\Observers\UserObserver;
use App\Observers\VenuePricingTierObserver;
use App\Observers\WalletTransactionObserver;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(JobFailed::class, LogQueueFailure::class);

        Review::observe(ReviewObserver::class);
        Booking::observe(BookingObserver::class);
        User::observe(UserObserver::class);
        VenuePricingTier::observe(VenuePricingTierObserver::class);
        Club::observe(ClubObserver::class);
        WalletTransaction::observe(WalletTransactionObserver::class);
        Referral::observe(ReferralObserver::class);
    }
}
