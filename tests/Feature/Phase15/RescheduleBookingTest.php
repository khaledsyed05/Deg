<?php

namespace Tests\Feature\Phase15;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Tests\TestCase;

class RescheduleBookingTest extends TestCase
{
    public function test_cannot_reschedule_started_booking(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => now()->subMinutes(30),
        ]);

        $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", [
            'new_slot_date' => now()->addDays(2)->format('Y-m-d'),
            'new_start_time' => '18:00',
            'new_end_time' => '19:00',
        ])->assertStatus(422);
    }

    public function test_cannot_reschedule_when_max_reached(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => now()->addDays(2),
            'reschedule_count' => 2,
        ]);

        $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", [
            'new_slot_date' => now()->addDays(3)->format('Y-m-d'),
            'new_start_time' => '18:00',
            'new_end_time' => '19:00',
        ])->assertStatus(422);
    }

    public function test_cannot_reschedule_other_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $other->id,
            'starts_at' => now()->addDays(5),
        ]);
        $this->actingAsPlayer();

        $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", [
            'new_slot_date' => now()->addDays(6)->format('Y-m-d'),
            'new_start_time' => '18:00',
            'new_end_time' => '19:00',
        ])->assertStatus(403);
    }

    public function test_cannot_reschedule_refunded_booking(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'starts_at' => now()->addDays(2),
            'refund_status' => 'fully_refunded',
        ]);

        $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", [
            'new_slot_date' => now()->addDays(3)->format('Y-m-d'),
            'new_start_time' => '18:00',
            'new_end_time' => '19:00',
        ])->assertStatus(422);
    }

    public function test_validation_rejects_past_dates(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'starts_at' => now()->addDays(2),
        ]);

        $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", [
            'new_slot_date' => now()->subDay()->format('Y-m-d'),
            'new_start_time' => '18:00',
            'new_end_time' => '19:00',
        ])->assertStatus(422);
    }
}
