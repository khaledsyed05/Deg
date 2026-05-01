<?php

namespace App\Services\Booking;

use App\Jobs\Notification\GroupBookingInviteNotificationJob;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GroupBookingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $captain): Booking
    {
        $splitData = $this->calculateSplit(
            (int) $data['total_price'],
            (int) $data['group_size'],
            (string) $data['payment_split_type'],
            $data['custom_splits'] ?? null,
        );

        return DB::transaction(function () use ($data, $captain, $splitData) {
            $date = Carbon::parse($data['booking_date'])->toDateString();
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            $booking = Booking::create([
                'user_id' => $captain->id,
                'captain_id' => $captain->id,
                'team_id' => $data['team_id'] ?? null,
                'venue_id' => $data['venue_id'],
                'booking_code' => Booking::generateBookingCode(),
                'source' => 'mobile',
                'status' => 'pending_payment',
                'booking_date' => $date,
                'start_time' => $startsAt->format('H:i:s'),
                'end_time' => $endsAt->format('H:i:s'),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => (int) $data['duration_minutes'],
                'venue_price' => (int) $data['total_price'],
                'total_price' => (int) $data['total_price'],
                'currency' => 'SYP',
                'is_group_booking' => true,
                'group_size' => (int) $data['group_size'],
                'payment_split_type' => $data['payment_split_type'],
                'payment_split_data' => $splitData,
            ]);

            $captainShare = $splitData['captain_share']
                ?? $splitData['per_player']
                ?? (int) $data['total_price'];

            $this->addParticipant($booking, $captain->id, (int) $captainShare);

            return $booking;
        });
    }

    /**
     * @param  array<int, array{user_id:int, amount:int}>|null  $customSplits
     * @return array<string, mixed>
     */
    protected function calculateSplit(int $totalPrice, int $groupSize, string $splitType, ?array $customSplits): array
    {
        $groupSize = max(1, $groupSize);

        return match ($splitType) {
            'full' => [
                'split_type' => 'full',
                'total_amount' => $totalPrice,
                'captain_share' => $totalPrice,
                'per_player' => 0,
            ],
            'equal' => [
                'split_type' => 'equal',
                'total_amount' => $totalPrice,
                'per_player' => intdiv($totalPrice, $groupSize),
            ],
            'custom' => [
                'split_type' => 'custom',
                'total_amount' => $totalPrice,
                'splits' => $customSplits ?? [],
            ],
            default => [
                'split_type' => $splitType,
                'total_amount' => $totalPrice,
            ],
        };
    }

    public function addParticipant(Booking $booking, int $userId, int $paymentShare): BookingParticipant
    {
        return BookingParticipant::updateOrCreate(
            ['booking_id' => $booking->id, 'user_id' => $userId],
            [
                'status' => 'invited',
                'payment_share' => $paymentShare,
                'payment_status' => 'pending',
                'invited_at' => now(),
            ],
        );
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, BookingParticipant>
     */
    public function invitePlayers(Booking $booking, array $userIds): array
    {
        $splitData = $booking->payment_split_data ?? [];
        $perPlayer = (int) ($splitData['per_player'] ?? 0);

        $invited = [];

        foreach (array_unique($userIds) as $userId) {
            $userId = (int) $userId;

            if ($userId === (int) $booking->captain_id) {
                continue;
            }

            if ($booking->participants()->where('user_id', $userId)->exists()) {
                continue;
            }

            $invited[] = $this->addParticipant($booking, $userId, $perPlayer);

            GroupBookingInviteNotificationJob::dispatch($booking, $userId);
        }

        return $invited;
    }
}
