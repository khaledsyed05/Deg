<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\RemainingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingTopUpSeeder extends Seeder
{
    /**
     * Top up bookings to ~200. Idempotent: skips if booking count already >= target.
     */
    public function run(): void
    {
        $target = 200;
        $existing = Booking::count();

        if ($existing >= $target) {
            $this->command->info("Bookings already at {$existing} — no top-up needed.");

            return;
        }

        $toCreate = $target - $existing;
        $users = User::whereNotNull('phone_verified_at')->limit(60)->pluck('id')->all();
        $venues = Venue::query()->where('status', 'active')->limit(40)->get();

        if (empty($users) || $venues->isEmpty()) {
            $this->command->warn('Not enough users or venues — aborting.');

            return;
        }

        // Distribution: 40% confirmed (future), 35% completed (past), 15% cancelled, 10% scheduled.
        $distribution = [
            BookingStatus::Confirmed->value => (int) round($toCreate * 0.40),
            BookingStatus::Completed->value => (int) round($toCreate * 0.35),
            BookingStatus::Cancelled->value => (int) round($toCreate * 0.15),
            BookingStatus::Scheduled->value => (int) round($toCreate * 0.10),
        ];

        $created = 0;

        foreach ($distribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $venue = $venues->random();
                $userId = $users[array_rand($users)];

                $isPast = in_array($status, [BookingStatus::Completed->value, BookingStatus::Cancelled->value], true);
                $daysOffset = $isPast ? random_int(-60, -1) : random_int(1, 30);
                $date = now()->addDays($daysOffset)->format('Y-m-d');

                $startHour = random_int(8, 20);
                $startTime = sprintf('%02d:00', $startHour);
                $durationMinutes = 60 * random_int(1, 3);
                $endTime = sprintf('%02d:00', $startHour + (int) ($durationMinutes / 60));
                $startsAt = Carbon::parse("{$date} {$startTime}");
                $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

                $pricePerHour = (int) ($venue->price_from ?: 50000);
                $venuePrice = (int) round($pricePerHour * $durationMinutes / 60);
                $commissionAmount = (int) ($venuePrice * 0.07);
                $depositAmount = (int) round($venuePrice * 0.30);

                $depositStatus = $status === BookingStatus::Scheduled->value
                    ? DepositStatus::None
                    : DepositStatus::Paid;
                $remainingStatus = $status === BookingStatus::Completed->value
                    ? RemainingStatus::Confirmed
                    : ($status === BookingStatus::Cancelled->value ? RemainingStatus::Waived : RemainingStatus::DueOnArrival);

                Booking::create([
                    'user_id' => $userId,
                    'venue_id' => $venue->id,
                    'booking_code' => Booking::generateBookingCode(),
                    'source' => 'mobile',
                    'status' => $status,
                    'booking_date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'duration_minutes' => $durationMinutes,
                    'venue_price' => $venuePrice,
                    'commission_amount' => $commissionAmount,
                    'commission_type' => 'percentage',
                    'total_price' => $venuePrice,
                    'club_payout_amount' => $venuePrice - $commissionAmount,
                    'currency' => 'SYP',
                    'deposit_amount' => $depositAmount,
                    'deposit_status' => $depositStatus,
                    'remaining_amount' => $venuePrice - $depositAmount,
                    'remaining_status' => $remainingStatus,
                    'cancellation_commission' => 0,
                    'cancelled_at' => $status === BookingStatus::Cancelled->value ? $startsAt->copy()->subDays(2) : null,
                    'cancellation_reason' => $status === BookingStatus::Cancelled->value ? 'تغيير في الخطة' : null,
                    'cancelled_by' => $status === BookingStatus::Cancelled->value ? $userId : null,
                    'created_at' => now()->subDays(random_int(1, 90)),
                ]);

                $created++;
            }
        }

        $this->command->info("BookingTopUpSeeder: created {$created} bookings (total now: ".Booking::count().').');
    }
}
