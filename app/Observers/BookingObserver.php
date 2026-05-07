<?php

namespace App\Observers;

use App\Enums\AchievementType;
use App\Enums\BookingStatus;
use App\Jobs\Notification\BookingCancelledNotificationJob;
use App\Jobs\Notification\BookingConfirmedNotificationJob;
use App\Jobs\Notification\BookingReminderJob;
use App\Jobs\Waitlist\NotifyWaitlistJob;
use App\Models\Booking;
use App\Models\PlayerStats;
use App\Models\User;
use App\Services\SportsProfile\WeeklyActivityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        $this->updateStatsOnCreate($booking);
        $this->forgetWeeklyActivityCache($booking);

        if ($booking->status !== BookingStatus::Confirmed) {
            return;
        }

        try {
            BookingConfirmedNotificationJob::dispatch($booking);

            $startsAt = Carbon::parse($booking->booking_date->format('Y-m-d').' '.$booking->start_time);

            if ($startsAt->diffInHours(now()) >= 2) {
                BookingReminderJob::dispatch($booking, hoursBeforeStart: 2)
                    ->delay($startsAt->copy()->subHours(2));
            }

            if ($startsAt->diffInHours(now()) >= 1) {
                BookingReminderJob::dispatch($booking, hoursBeforeStart: 1)
                    ->delay($startsAt->copy()->subHour());
            }
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to dispatch confirmation jobs', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->isDirty('status')) {
            return;
        }

        $this->updateStatsOnStatusChange($booking);
        $this->forgetWeeklyActivityCache($booking);

        if ($booking->status !== BookingStatus::Cancelled) {
            return;
        }

        try {
            BookingCancelledNotificationJob::dispatch($booking);

            NotifyWaitlistJob::dispatch(
                $booking->venue_id,
                $booking->booking_date,
                $booking->start_time,
                $booking->duration_minutes,
            );
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to dispatch cancellation jobs', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(Booking $booking): void
    {
        $this->forgetWeeklyActivityCache($booking);
    }

    /**
     * Invalidate the user's weekly-activity cache so the next read
     * recomputes. Called from created/updated/deleted hooks. Failure
     * is non-fatal — the cache will roll over at the next 15-minute
     * TTL anyway.
     */
    protected function forgetWeeklyActivityCache(Booking $booking): void
    {
        $user = $booking->user;

        if (! $user) {
            return;
        }

        try {
            app(WeeklyActivityService::class)->forget($user);
        } catch (\Throwable $e) {
            Log::warning('BookingObserver: failed to invalidate weekly activity cache', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function updateStatsOnCreate(Booking $booking): void
    {
        $user = $booking->user;

        if (! $user) {
            return;
        }

        try {
            $stats = PlayerStats::firstOrCreate(['user_id' => $user->id]);
            $stats->incrementBookings();

            $this->checkFirstBooking($user);
            $this->checkEarlyBird($booking, $user);
            $this->checkNightOwl($booking, $user);
            $this->checkVenueExplorer($user);
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to update stats on create', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function updateStatsOnStatusChange(Booking $booking): void
    {
        $user = $booking->user;

        if (! $user) {
            return;
        }

        try {
            $stats = PlayerStats::firstOrCreate(['user_id' => $user->id]);

            if ($booking->status === BookingStatus::Completed) {
                $durationHours = max(1, (int) round(((int) $booking->duration_minutes) / 60));
                $stats->recordCompletion($durationHours, (int) $booking->total_price);
                $stats->recalculateFavoriteSport();
                $stats->recalculateFavoriteVenue();

                $this->checkLoyalPlayer($user);
                $this->checkBigSpender($user);
            }

            if ($booking->status === BookingStatus::Cancelled) {
                $stats->increment('cancelled_bookings');
            }
        } catch (\Throwable $e) {
            Log::error('BookingObserver: failed to update stats on status change', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkFirstBooking(User $user): void
    {
        $achievement = $user->achievements()->firstOrCreate(
            ['type' => AchievementType::FirstBooking->value],
            ['progress' => 0, 'target' => 1],
        );

        if (! $achievement->isUnlocked()) {
            $achievement->updateProgress(1);
        }
    }

    protected function checkEarlyBird(Booking $booking, User $user): void
    {
        $hour = Carbon::parse($booking->start_time)->hour;

        if ($hour < 8) {
            $achievement = $user->achievements()->firstOrCreate(
                ['type' => AchievementType::EarlyBird->value],
                ['progress' => 0, 'target' => 10],
            );
            $achievement->updateProgress(1);
        }
    }

    protected function checkNightOwl(Booking $booking, User $user): void
    {
        $hour = Carbon::parse($booking->start_time)->hour;

        if ($hour >= 20) {
            $achievement = $user->achievements()->firstOrCreate(
                ['type' => AchievementType::NightOwl->value],
                ['progress' => 0, 'target' => 10],
            );
            $achievement->updateProgress(1);
        }
    }

    protected function checkLoyalPlayer(User $user): void
    {
        $completed = (int) ($user->playerStats?->completed_bookings ?? 0);

        $achievement = $user->achievements()->firstOrCreate(
            ['type' => AchievementType::LoyalPlayer->value],
            ['progress' => 0, 'target' => 50],
        );

        $achievement->progress = $completed;
        $achievement->save();

        if ($completed >= 50 && ! $achievement->isUnlocked()) {
            $achievement->unlock();
        }
    }

    protected function checkVenueExplorer(User $user): void
    {
        $unique = (int) $user->bookings()->distinct('venue_id')->count('venue_id');

        $achievement = $user->achievements()->firstOrCreate(
            ['type' => AchievementType::VenueExplorer->value],
            ['progress' => 0, 'target' => 10],
        );

        $achievement->progress = $unique;
        $achievement->save();

        if ($unique >= 10 && ! $achievement->isUnlocked()) {
            $achievement->unlock();
        }
    }

    protected function checkBigSpender(User $user): void
    {
        $totalSpent = (int) ($user->playerStats?->total_spent ?? 0);

        $achievement = $user->achievements()->firstOrCreate(
            ['type' => AchievementType::BigSpender->value],
            ['progress' => 0, 'target' => 5000000],
        );

        $achievement->progress = $totalSpent;
        $achievement->save();

        if ($totalSpent >= 5000000 && ! $achievement->isUnlocked()) {
            $achievement->unlock();
        }
    }
}
