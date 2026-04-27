<?php

namespace App\Services\Booking;

use App\Enums\CreditType;
use App\Exceptions\Booking\RefundException;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\Booking\RefundCompleted;
use App\Notifications\Booking\RefundRequested;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function requestRefund(Booking $booking, User $user, string $reason, string $refundMethod = 'wallet'): RefundRequest
    {
        if ($booking->user_id !== $user->id) {
            throw new RefundException('Not your booking', 403);
        }

        $this->checkEligibility($booking);

        $calc = $this->calculateRefundAmount($booking);
        if ($calc['amount'] <= 0) {
            throw new RefundException("No refund available: {$calc['reason']}", 422);
        }

        $threshold = (int) config('bookings.refund.auto_approve_threshold', 50000);
        $autoApprove = $calc['amount'] <= $threshold;

        return DB::transaction(function () use ($booking, $user, $reason, $refundMethod, $calc, $autoApprove) {
            $request = RefundRequest::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'requested_amount' => $booking->total_price,
                'approved_amount' => $autoApprove ? $calc['amount'] : null,
                'reason' => $reason,
                'refund_method' => $refundMethod,
                'policy_applied' => $calc['reason'],
                'auto_approved' => $autoApprove,
                'status' => $autoApprove ? 'approved' : 'pending_review',
            ]);

            $booking->update(['refund_status' => 'requested']);

            if ($autoApprove) {
                $this->processRefund($request);
            }

            $user->notify(new RefundRequested($request));

            return $request->fresh();
        });
    }

    public function processRefund(RefundRequest $request): void
    {
        if ($request->status !== 'approved') {
            throw new RefundException('Refund must be approved before processing');
        }

        $request->update(['status' => 'processing']);

        try {
            if ($request->refund_method === 'wallet') {
                $user = $request->user;
                $wallet = $user->walletOrCreate();
                $wallet->credit(
                    (int) round((float) $request->approved_amount),
                    CreditType::REFUND,
                    "Refund for booking #{$request->booking_id}",
                    $request->booking,
                    null,
                    ['refund_request_id' => $request->id, 'booking_id' => $request->booking_id]
                );

                $request->update(['status' => 'completed', 'processed_at' => now()]);

                $newStatus = (float) $request->approved_amount >= (float) $request->booking->total_price
                    ? 'fully_refunded'
                    : 'partially_refunded';
                $request->booking->update(['refund_status' => $newStatus]);

                $user->notify(new RefundCompleted($request));
            } else {
                $request->update(['status' => 'pending_review']);
            }
        } catch (\Throwable $e) {
            $request->update(['status' => 'failed', 'rejection_reason' => $e->getMessage()]);
            Log::channel('single')->error('Refund processing failed', [
                'refund_request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function calculateRefundAmount(Booking $booking): array
    {
        $hours = (int) now()->diffInHours(Carbon::parse($booking->starts_at), false);
        $policy = config('bookings.refund.cancellation_policy', [48 => 100, 24 => 75, 6 => 50, 1 => 25, 0 => 0]);
        krsort($policy);

        foreach ($policy as $tierHours => $percentage) {
            if ($hours >= $tierHours) {
                return [
                    'amount' => (float) $booking->total_price * ($percentage / 100),
                    'percentage' => $percentage,
                    'reason' => "{$percentage}% refund ({$tierHours}h+ before booking)",
                    'hours_until_booking' => $hours,
                ];
            }
        }

        return ['amount' => 0.0, 'percentage' => 0, 'reason' => 'No refund (less than 1h before booking)', 'hours_until_booking' => $hours];
    }

    private function checkEligibility(Booking $booking): void
    {
        if (in_array($booking->refund_status, ['fully_refunded', 'partially_refunded'], true)) {
            throw new RefundException('Booking already refunded', 422);
        }

        $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        if ($status === 'cancelled') {
            throw new RefundException('Cannot refund cancelled booking', 422);
        }

        if (RefundRequest::where('booking_id', $booking->id)
            ->whereIn('status', ['pending_review', 'approved', 'processing'])
            ->exists()) {
            throw new RefundException('Refund request already exists for this booking', 422);
        }
    }
}
