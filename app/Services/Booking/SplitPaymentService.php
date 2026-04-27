<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\SplitPaymentException;
use App\Models\Booking;
use App\Models\BookingPaymentSplit;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SplitPaymentService
{
    /**
     * @param  array<int, array{user_id: int, amount: float|int}>  $splits
     * @return array<string, mixed>
     */
    public function createSplit(Booking $booking, User $initiator, string $method, array $splits): array
    {
        if ($booking->user_id !== $initiator->id && $booking->captain_id !== $initiator->id) {
            throw new SplitPaymentException('غير مصرح لك بتقسيم الدفع', 403);
        }

        if ($booking->is_split_payment ?? false) {
            throw new SplitPaymentException('الحجز مقسّم بالفعل');
        }

        $totalSplits = array_sum(array_map(fn ($s) => (float) ($s['amount'] ?? 0), $splits));
        $bookingTotal = (float) $booking->total_price;

        if (abs($totalSplits - $bookingTotal) > 0.01) {
            throw new SplitPaymentException(
                "مجموع الأقسام ({$totalSplits}) لا يساوي إجمالي الحجز ({$bookingTotal})"
            );
        }

        if ($booking->team_id) {
            $teamMemberIds = TeamMember::where('team_id', $booking->team_id)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();

            foreach ($splits as $split) {
                if (! in_array((int) $split['user_id'], $teamMemberIds, true)) {
                    throw new SplitPaymentException(
                        "المستخدم {$split['user_id']} ليس عضواً في الفريق"
                    );
                }
            }
        }

        return DB::transaction(function () use ($booking, $method, $splits, $initiator) {
            $booking->update([
                'is_split_payment' => true,
                'split_method' => $method,
            ]);

            $created = [];
            foreach ($splits as $split) {
                $isInitiator = (int) $split['user_id'] === $initiator->id;
                $created[] = BookingPaymentSplit::create([
                    'booking_id' => $booking->id,
                    'user_id' => (int) $split['user_id'],
                    'amount' => (float) $split['amount'],
                    'status' => $isInitiator ? 'paid' : 'pending',
                    'payment_due_at' => now()->addDays(2),
                    'paid_at' => $isInitiator ? now() : null,
                ]);
            }

            $initiatorShare = collect($created)->firstWhere('user_id', $initiator->id);

            return [
                'booking_id' => $booking->id,
                'total_amount' => (float) $booking->total_price,
                'splits' => array_map(fn (BookingPaymentSplit $s) => [
                    'user_id' => $s->user_id,
                    'user_name' => User::find($s->user_id)?->name,
                    'amount' => (float) $s->amount,
                    'status' => $s->status,
                    'payment_due_at' => $s->payment_due_at?->toIso8601String(),
                ], $created),
                'your_share' => $initiatorShare ? (float) $initiatorShare->amount : 0,
                'your_status' => $initiatorShare?->status ?? 'pending',
            ];
        });
    }
}
