<?php

namespace App\Services\SportsProfile;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class WeeklyActivityService
{
    private const CACHE_TTL_MINUTES = 15;

    private const ACTIVE_STATUSES = [
        BookingStatus::Confirmed,
        BookingStatus::Completed,
        BookingStatus::CheckedIn,
        BookingStatus::Scheduled,
    ];

    /**
     * Return up to {weeks} buckets of (week_start, bookings_count,
     * hours_played), oldest first, zero-filled when a week has no
     * activity.
     *
     * @return array<int, array{week_start: string, bookings_count: int, hours_played: int}>
     */
    public function get(User $user, int $weeks = 12): array
    {
        $key = $this->cacheKey($user);

        return Cache::remember(
            $key,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->compute($user, $weeks),
        );
    }

    public function forget(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    /**
     * @return array<int, array{week_start: string, bookings_count: int, hours_played: int}>
     */
    private function compute(User $user, int $weeks): array
    {
        $today = CarbonImmutable::now()->startOfWeek();
        $oldest = $today->subWeeks($weeks - 1);

        $buckets = [];
        for ($i = 0; $i < $weeks; $i++) {
            $monday = $oldest->addWeeks($i);
            $buckets[$monday->toDateString()] = [
                'week_start' => $monday->toDateString(),
                'bookings_count' => 0,
                'hours_played' => 0,
            ];
        }

        $bookings = Booking::query()
            ->forUser($user->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, self::ACTIVE_STATUSES))
            ->where('starts_at', '>=', $oldest->toDateTimeString())
            ->where('starts_at', '<', $today->addWeek()->toDateTimeString())
            ->get(['starts_at', 'duration_minutes']);

        foreach ($bookings as $booking) {
            $monday = CarbonImmutable::instance($booking->starts_at)->startOfWeek()->toDateString();

            if (! isset($buckets[$monday])) {
                continue;
            }

            $buckets[$monday]['bookings_count']++;
            $buckets[$monday]['hours_played'] += (int) round(((int) $booking->duration_minutes) / 60);
        }

        return array_values($buckets);
    }

    private function cacheKey(User $user): string
    {
        $weekStart = CarbonImmutable::now()->startOfWeek()->toDateString();

        return "sports_profile:weekly:{$user->id}:{$weekStart}";
    }
}
