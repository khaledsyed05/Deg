<?php

namespace App\Services\Booking;

use App\DTOs\Booking\SlotAvailabilityResult;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Venue;
use Carbon\Carbon;

/**
 * High-level availability facade for the mobile API.
 *
 * Delegates single-slot checks to the existing SlotAvailabilityService (which owns
 * the optimistic-lock + overlap logic) and adds a daily-slot enumerator for the
 * mobile "pick a time" UI.
 */
class AvailabilityService
{
    public function __construct(
        private SlotAvailabilityService $slotAvailability,
    ) {}

    /**
     * Check a single slot. Delegates to SlotAvailabilityService.
     */
    public function check(int $venueId, string $date, string $startTime, int $durationMinutes): SlotAvailabilityResult
    {
        return $this->slotAvailability->check(
            venueId: $venueId,
            date: $date,
            startTime: $startTime,
            durationMinutes: $durationMinutes,
        );
    }

    /**
     * Enumerate 1-hour slots across a venue's opening hours for a given date.
     * Marks each slot available/busy based on overlapping confirmed bookings.
     *
     * @return array<int, array{start_time: string, end_time: string, available: bool}>
     */
    public function getAvailableSlots(int $venueId, string $date, int $slotMinutes = 60): array
    {
        $venue = Venue::findOrFail($venueId);
        $day = strtolower(Carbon::parse($date)->format('l'));
        $hours = $venue->opening_hours[$day] ?? null;

        if (! is_array($hours) || ($hours['closed'] ?? false)) {
            return [];
        }

        $open = $hours['open'] ?? '08:00';
        $close = $hours['close'] ?? '23:00';

        $bookings = Booking::query()
            ->where('venue_id', $venueId)
            ->where('booking_date', $date)
            ->whereIn('status', [
                BookingStatus::Confirmed,
                BookingStatus::Scheduled,
                BookingStatus::CheckedIn,
                BookingStatus::PendingPayment,
            ])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $slots = [];
        $cursor = Carbon::parse("{$date} {$open}");
        $closeAt = Carbon::parse("{$date} {$close}");

        while ($cursor->copy()->addMinutes($slotMinutes)->lte($closeAt)) {
            $slotStart = $cursor->format('H:i');
            $slotEnd = $cursor->copy()->addMinutes($slotMinutes)->format('H:i');

            $busy = $bookings->contains(fn ($b) => $slotStart < $b->end_time && $slotEnd > $b->start_time);

            $slots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'available' => ! $busy,
            ];

            $cursor->addMinutes($slotMinutes);
        }

        return $slots;
    }
}
