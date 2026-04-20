<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenuePricingTier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Players: IDs 7-16 (indices 0-9 in players array)
        $playerIds = User::role('player')->pluck('id')->toArray();
        $activeVenues = Venue::where('status', 'active')->get();

        if ($activeVenues->isEmpty() || empty($playerIds)) {
            $this->command->warn('No active venues or players found — skipping BookingSeeder');

            return;
        }

        // Status distribution: confirmed 60%, scheduled 20%, completed 15%, cancelled 5%
        $statuses = array_merge(
            array_fill(0, 12, BookingStatus::Confirmed->value),
            array_fill(0, 4, BookingStatus::Scheduled->value),
            array_fill(0, 3, BookingStatus::Completed->value),
            array_fill(0, 1, BookingStatus::Cancelled->value),
        );

        // Evening start times (most popular)
        $startTimes = ['16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
        $durations = [60, 60, 60, 90, 90, 120]; // minutes
        $today = Carbon::today();

        for ($i = 0; $i < 20; $i++) {
            $venue = $activeVenues->random();
            $userId = $playerIds[array_rand($playerIds)];
            $status = $statuses[$i % count($statuses)];
            $startTime = $startTimes[$i % count($startTimes)];
            $duration = $durations[$i % count($durations)];

            // Distribute bookings: past 7 days + next 14 days
            $dayOffset = ($i % 3 === 0) ? random_int(-7, 0) : random_int(1, 14);
            $bookingDate = $today->copy()->addDays($dayOffset)->toDateString();

            [$startH, $startM] = explode(':', $startTime);
            $endTime = Carbon::createFromTime((int) $startH, (int) $startM)
                ->addMinutes($duration)
                ->format('H:i');

            $startsAt = Carbon::parse("{$bookingDate} {$startTime}");
            $endsAt = Carbon::parse("{$bookingDate} {$endTime}");

            // Price from venue pricing tier
            $tier = VenuePricingTier::where('venue_id', $venue->id)->first();
            $venuePrice = $tier ? (int) ($tier->price * $duration / 60) : 50000;
            $commissionAmount = (int) ($venuePrice * 0.07);
            $totalPrice = $venuePrice;
            $clubPayout = $venuePrice - $commissionAmount;

            // 70% deposit, 30% full
            $useDeposit = ($i % 10) < 7;
            $depositAmount = $useDeposit ? (int) ($totalPrice * 0.30) : 0;
            $remainingAmount = $useDeposit ? ($totalPrice - $depositAmount) : 0;
            $depositStatus = $useDeposit ? 'paid' : 'none';
            $remainingStatus = $useDeposit
                ? (($i % 3 === 0) ? 'confirmed' : 'due_on_arrival')
                : 'none';

            $cancelledAt = $status === BookingStatus::Cancelled->value ? now() : null;

            Booking::create([
                'user_id'                 => $userId,
                'venue_id'                => $venue->id,
                'booking_code'            => 'BK' . strtoupper(Str::random(8)),
                'source'                  => 'mobile',
                'status'                  => $status,
                'booking_date'            => $bookingDate,
                'start_time'              => $startTime,
                'end_time'                => $endTime,
                'starts_at'               => $startsAt,
                'ends_at'                 => $endsAt,
                'duration_minutes'        => $duration,
                'venue_price'             => $venuePrice,
                'commission_amount'       => $commissionAmount,
                'commission_type'         => 'percentage',
                'total_price'             => $totalPrice,
                'club_payout_amount'      => $clubPayout,
                'cancellation_commission' => 0,
                'currency'                => 'SYP',
                'deposit_amount'          => $depositAmount,
                'deposit_status'          => $depositStatus,
                'remaining_amount'        => $remainingAmount,
                'remaining_status'        => $remainingStatus,
                'cancelled_at'            => $cancelledAt,
                'cancellation_reason'     => $cancelledAt ? 'إلغاء من قبل اللاعب' : null,
                'cancelled_by'            => $cancelledAt ? $userId : null,
            ]);
        }

        $this->command->info('✓ Bookings seeded: ' . Booking::count() . ' bookings');
    }
}
