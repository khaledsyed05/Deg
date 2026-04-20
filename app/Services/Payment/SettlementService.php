<?php

namespace App\Services\Payment;

use App\Models\Settlement;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SettlementItemRepositoryInterface;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        private SettlementRepositoryInterface $settlementRepo,
        private SettlementItemRepositoryInterface $itemRepo,
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    public function draft(int $clubId, string $periodFrom, string $periodTo, int $createdBy): Settlement
    {
        return DB::transaction(function () use ($clubId, $periodFrom, $periodTo, $createdBy) {
            $bookings = $this->bookingRepo->findPendingSettlement($clubId, $periodFrom, $periodTo);

            $totalVenuePrice = $bookings->sum('venue_price');
            $totalCommission = $bookings->sum('commission_amount');
            $totalCancellationFees = $bookings->sum('cancellation_commission');
            $netPayable = $totalVenuePrice - $totalCommission;

            $settlement = $this->settlementRepo->create([
                'club_id' => $clubId,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'total_bookings' => $bookings->count(),
                'total_venue_price' => $totalVenuePrice,
                'total_commission' => $totalCommission,
                'total_cancellation_fees' => $totalCancellationFees,
                'net_payable' => max(0, $netPayable),
                'paid_amount' => 0,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            foreach ($bookings as $booking) {
                $this->itemRepo->create([
                    'settlement_id' => $settlement->id,
                    'booking_id' => $booking->id,
                    'venue_price' => $booking->venue_price,
                    'commission_amount' => $booking->commission_amount,
                    'club_payout_amount' => $booking->club_payout_amount,
                    'cancellation_comm' => $booking->cancellation_commission,
                ]);
            }

            return $settlement;
        });
    }

    public function markCompleted(Settlement $settlement, int $paidAmount, string $method, ?string $reference, int $settledBy): Settlement
    {
        return $this->settlementRepo->update($settlement, [
            'status' => 'completed',
            'paid_amount' => $paidAmount,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'settled_by' => $settledBy,
            'settled_at' => now(),
        ]);
    }
}
