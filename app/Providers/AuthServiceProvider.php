<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Review;
use App\Models\Settlement;
use App\Models\Venue;
use App\Models\VenueWaitlist;
use App\Policies\BookingPolicy;
use App\Policies\ClubPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SettlementPolicy;
use App\Policies\VenuePolicy;
use App\Policies\VenueWaitlistPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Club::class => ClubPolicy::class,
        Venue::class => VenuePolicy::class,
        Review::class => ReviewPolicy::class,
        Settlement::class => SettlementPolicy::class,
        VenueWaitlist::class => VenueWaitlistPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
