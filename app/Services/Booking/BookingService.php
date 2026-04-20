<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingResult;
use App\DTOs\Booking\CancellationResult;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\RemainingStatus;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Payment\CommissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private SlotReservationService $slotReservationService,
        private SlotAvailabilityService $slotAvailabilityService,
        private CommissionService $commissionService,
    ) {}

    public function create(int $userId, int $venueId, array $data): BookingResult
    {
        return DB::transaction(function () use ($userId, $venueId, $data) {
            // 1. Check availability
            $availability = $this->slotAvailabilityService->check(
                $venueId,
                $data['booking_date'],
                $data['start_time'],
                $data['duration_minutes'],
            );

            if (! $availability->available) {
                throw new RuntimeException($availability->unavailableReason);
            }

            // 2. Reserve slot (10-min lock)
            $this->slotReservationService->reserve(
                userId: $userId,
                venueId: $venueId,
                date: $data['booking_date'],
                startTime: $data['start_time'],
                durationMinutes: $data['duration_minutes'],
                categoryId: $data['sport_category_id'] ?? null,
            );

            // 3. Calculate commission
            $commissionConfig = $this->commissionService->resolveConfig($venueId);
            $venuePrice = (int) $data['venue_price'];
            $paymentMode = $data['payment_mode'] ?? 'full';

            if ($paymentMode === 'deposit') {
                $depositAmount = (int) $data['deposit_amount'];
                $commission = $this->commissionService->calculateForDeposit($depositAmount, $commissionConfig);
            } else {
                $depositAmount = 0;
                $commission = $this->commissionService->calculate($venuePrice, $commissionConfig);
            }

            $remainingAmount = $paymentMode === 'deposit' ? ($venuePrice - $depositAmount) : 0;
            $startsAt = Carbon::parse("{$data['booking_date']} {$data['start_time']}");
            $endsAt = (clone $startsAt)->addMinutes((int) $data['duration_minutes']);

            // 4. Create booking
            $booking = $this->bookingRepo->create([
                'user_id' => $userId,
                'venue_id' => $venueId,
                'sport_category_id' => $data['sport_category_id'] ?? null,
                'booking_code' => $this->generateBookingCode(),
                'source' => BookingSource::Mobile,
                'status' => BookingStatus::Confirmed,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $endsAt->format('H:i:s'),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $data['duration_minutes'],
                'venue_price' => $venuePrice,
                'commission_amount' => $commission->commissionAmount,
                'commission_type' => $commission->commissionType,
                'total_price' => $commission->totalPrice,
                'club_payout_amount' => $commission->clubPayoutAmount,
                'deposit_amount' => $depositAmount,
                'deposit_status' => $depositAmount > 0 ? DepositStatus::None : DepositStatus::None,
                'remaining_amount' => $remainingAmount,
                'remaining_status' => $remainingAmount > 0 ? RemainingStatus::DueOnArrival : RemainingStatus::None,
                'currency' => $data['currency'] ?? 'SYP',
                'notes' => $data['notes'] ?? null,
            ]);

            return new BookingResult($booking, $commission);
        });
    }

    public function cancel(Booking $booking, int $cancelledBy, string $reason): CancellationResult
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Scheduled])) {
            throw new RuntimeException('Only confirmed or scheduled bookings can be cancelled.');
        }

        return DB::transaction(function () use ($booking, $cancelledBy, $reason) {
            $this->bookingRepo->update($booking, [
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);

            return new CancellationResult(
                booking: $booking->fresh(),
                refundAmount: 0, // refund logic handled by payment service
                walletCredited: false,
            );
        });
    }

    private function generateBookingCode(): string
    {
        return 'BK-'.strtoupper(Str::random(8));
    }
}
