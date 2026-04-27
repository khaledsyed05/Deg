<?php

namespace App\Services\Booking;

use App\Enums\CreditType;
use App\Exceptions\Booking\RescheduleException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Booking\BookingRescheduled;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RescheduleService
{
    public function __construct(
        private readonly SlotAvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    public function reschedule(
        Booking $booking,
        User $user,
        string $newDate,
        string $newStartTime,
        string $newEndTime,
        ?string $reason = null,
    ): Booking {
        if ($booking->user_id !== $user->id) {
            throw new RescheduleException('Not your booking', 403);
        }

        $this->checkEligibility($booking);

        $newStart = Carbon::parse("{$newDate} {$newStartTime}");
        $newEnd = Carbon::parse("{$newDate} {$newEndTime}");
        $duration = $newEnd->diffInMinutes($newStart);
        if ($duration <= 0) {
            throw new RescheduleException('Invalid time range', 422);
        }

        $availability = $this->availability->check($booking->venue_id, $newDate, $newStartTime, $duration);
        $available = is_object($availability)
            ? ($availability->available ?? $availability->isAvailable ?? false)
            : (bool) $availability;
        if (! $available) {
            throw new RescheduleException('New slot is not available', 422);
        }

        $pricing = $this->pricing->calculate($booking->venue_id, $duration);
        $newPrice = (float) ($pricing['total'] ?? $pricing['total_price'] ?? $pricing['venue_price'] ?? $booking->total_price);
        $priceDiff = (float) ($newPrice - (float) $booking->total_price);

        return DB::transaction(function () use ($booking, $user, $newDate, $newStartTime, $newEndTime, $newStart, $newEnd, $newPrice, $priceDiff, $reason) {
            $history = $booking->reschedule_history ?? [];
            $history[] = [
                'from' => [
                    'date' => $booking->booking_date instanceof CarbonInterface
                        ? $booking->booking_date->format('Y-m-d')
                        : (string) $booking->booking_date,
                    'start_time' => (string) $booking->start_time,
                    'end_time' => (string) $booking->end_time,
                ],
                'to' => [
                    'date' => $newDate,
                    'start_time' => $newStartTime,
                    'end_time' => $newEndTime,
                ],
                'at' => now()->toIso8601String(),
                'by_user_id' => $user->id,
                'reason' => $reason,
                'price_difference' => $priceDiff,
            ];

            $booking->update([
                'booking_date' => $newDate,
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'starts_at' => $newStart,
                'ends_at' => $newEnd,
                'total_price' => $newPrice,
                'reschedule_count' => (int) $booking->reschedule_count + 1,
                'reschedule_history' => $history,
            ]);

            if ($priceDiff > 0) {
                $wallet = $user->walletOrCreate();
                if ((int) $wallet->available < (int) $priceDiff) {
                    throw new RescheduleException('Insufficient wallet balance for price difference', 402);
                }
                $wallet->debit(
                    (int) round($priceDiff),
                    CreditType::BOOKING,
                    "Reschedule price difference for booking #{$booking->id}",
                    $booking,
                    ['booking_id' => $booking->id]
                );
            } elseif ($priceDiff < 0) {
                $wallet = $user->walletOrCreate();
                $wallet->credit(
                    (int) round(abs($priceDiff)),
                    CreditType::REFUND,
                    "Reschedule refund for booking #{$booking->id}",
                    $booking,
                    null,
                    ['booking_id' => $booking->id]
                );
            }

            $user->notify(new BookingRescheduled($booking->fresh()));

            return $booking->fresh();
        });
    }

    private function checkEligibility(Booking $booking): void
    {
        $allowedStatuses = ['confirmed', 'pending_payment'];
        $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        if (! in_array($status, $allowedStatuses, true)) {
            throw new RescheduleException("Booking cannot be rescheduled (status: {$status})", 422);
        }

        $minMinutes = (int) config('bookings.reschedule.min_minutes_before', 60);
        if (Carbon::parse($booking->starts_at)->lte(now()->addMinutes($minMinutes))) {
            throw new RescheduleException("Reschedule must be requested at least {$minMinutes} minutes before booking", 422);
        }

        $maxReschedules = (int) config('bookings.reschedule.max_per_booking', 2);
        if ((int) $booking->reschedule_count >= $maxReschedules) {
            throw new RescheduleException("Maximum reschedules ({$maxReschedules}) reached", 422);
        }

        if (in_array($booking->refund_status, ['fully_refunded', 'partially_refunded'], true)) {
            throw new RescheduleException('Refunded bookings cannot be rescheduled', 422);
        }
    }
}
