<?php

namespace App\Services\Booking;

use App\Models\SlotReservation;
use App\Repositories\Contracts\SlotReservationRepositoryInterface;
use Carbon\Carbon;
use RuntimeException;

class SlotReservationService
{
    /** Reservation TTL in minutes */
    private const RESERVATION_MINUTES = 10;

    public function __construct(
        private SlotReservationRepositoryInterface $reservationRepo,
    ) {}

    public function reserve(
        int $userId,
        int $venueId,
        string $date,
        string $startTime,
        int $durationMinutes,
        ?int $categoryId = null,
    ): SlotReservation {
        // Clean any expired reservation for this slot first
        $this->reservationRepo->deleteExpired();

        $existing = $this->reservationRepo->findActive($venueId, $date, $startTime);

        if ($existing) {
            throw new RuntimeException('Slot is already reserved. Please try again shortly.');
        }

        $endTime = Carbon::parse("{$date} {$startTime}")
            ->addMinutes($durationMinutes)
            ->format('H:i:s');

        return $this->reservationRepo->create([
            'venue_id' => $venueId,
            'user_id' => $userId,
            'booking_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'category_id' => $categoryId,
            'reserved_until' => now()->addMinutes(self::RESERVATION_MINUTES),
        ]);
    }

    public function release(SlotReservation $reservation): void
    {
        $this->reservationRepo->delete($reservation);
    }

    public function releaseForUser(int $userId): void
    {
        $this->reservationRepo->deleteForUser($userId);
    }
}
