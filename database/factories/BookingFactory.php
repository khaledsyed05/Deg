<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $bookingDate = fake()->dateTimeBetween('+1 day', '+30 days');
        $startTime = fake()->randomElement(['16:00', '17:00', '18:00', '19:00', '20:00']);
        $startsAt = \Carbon\Carbon::parse($bookingDate->format('Y-m-d') . ' ' . $startTime);
        $endsAt = $startsAt->copy()->addHour();
        $venuePrice = fake()->randomElement([40000, 45000, 50000]);
        $commissionAmount = (int) ($venuePrice * 0.07);

        return [
            'user_id' => User::factory(),
            'venue_id' => Venue::factory(),
            'booking_code' => 'BK' . strtoupper(Str::random(8)),
            'source' => 'mobile',
            'status' => BookingStatus::Confirmed,
            'booking_date' => $bookingDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endsAt->format('H:i'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => 60,
            'venue_price' => $venuePrice,
            'commission_amount' => $commissionAmount,
            'commission_type' => 'percentage',
            'total_price' => $venuePrice,
            'club_payout_amount' => $venuePrice - $commissionAmount,
            'cancellation_commission' => 0,
            'currency' => 'SYP',
            'deposit_amount' => 0,
            'deposit_status' => 'none',
            'remaining_amount' => 0,
            'remaining_status' => 'none',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Completed,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHour(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
