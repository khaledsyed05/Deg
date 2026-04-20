<?php

namespace App\Services\Booking;

use App\DTOs\Booking\SlotAvailabilityResult;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SlotReservationRepositoryInterface;
use App\Repositories\Contracts\VenueRepositoryInterface;
use Carbon\Carbon;

class SlotAvailabilityService
{
    public function __construct(
        private VenueRepositoryInterface $venueRepo,
        private BookingRepositoryInterface $bookingRepo,
        private SlotReservationRepositoryInterface $reservationRepo,
    ) {}

    public function check(int $venueId, string $date, string $startTime, int $durationMinutes): SlotAvailabilityResult
    {
        $venue = $this->venueRepo->findOrFail($venueId);

        // 1. Check venue is active
        if ($venue->status->value !== 'active') {
            return new SlotAvailabilityResult(false, 'Venue is not active.');
        }

        // 2. Check date is not in the past
        if (Carbon::parse($date)->isPast() && Carbon::parse($date)->isToday() === false) {
            return new SlotAvailabilityResult(false, 'Booking date is in the past.');
        }

        $endTime = Carbon::parse("{$date} {$startTime}")
            ->addMinutes($durationMinutes)
            ->format('H:i:s');

        // 3. Check for active slot reservation (optimistic lock)
        $reserved = $this->reservationRepo->findActive($venueId, $date, $startTime);
        if ($reserved) {
            return new SlotAvailabilityResult(false, 'Slot is temporarily held by another user.');
        }

        // 4. Check for confirmed/scheduled bookings overlapping
        $overlapping = $this->bookingRepo->findConfirmedOverlapping(
            $venueId, $date, $startTime, $endTime
        );

        if ($overlapping->isNotEmpty()) {
            return new SlotAvailabilityResult(false, 'Slot is already booked.');
        }

        return new SlotAvailabilityResult(true);
    }
}
