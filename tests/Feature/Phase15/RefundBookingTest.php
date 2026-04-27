<?php

namespace Tests\Feature\Phase15;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\User;
use Tests\TestCase;

class RefundBookingTest extends TestCase
{
    public function test_full_refund_auto_approved_when_far_in_advance(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'total_price' => 30000,
            'starts_at' => now()->addDays(7),
        ]);

        $this->postJson("/api/v1/bookings/{$booking->id}/refund", [
            'reason' => 'تعارض موعد',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.auto_approved', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('refund_requests', [
            'booking_id' => $booking->id,
            'auto_approved' => true,
        ]);
        $this->assertEquals('fully_refunded', $booking->fresh()->refund_status);
    }

    public function test_no_refund_when_less_than_one_hour_before(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => now()->addMinutes(30),
        ]);

        $this->postJson("/api/v1/bookings/{$booking->id}/refund", ['reason' => 'late cancel'])
            ->assertStatus(422);
    }

    public function test_partial_refund_at_24h_tier(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
            'total_price' => 40000,
            'starts_at' => now()->addHours(30),
        ]);

        $resp = $this->postJson("/api/v1/bookings/{$booking->id}/refund", ['reason' => 'changed mind'])
            ->assertStatus(200);

        $approved = (float) $resp->json('data.approved_amount');
        $this->assertEqualsWithDelta(30000.0, $approved, 0.01); // 75%
    }

    public function test_cannot_refund_already_refunded_booking(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'starts_at' => now()->addDays(7),
            'refund_status' => 'fully_refunded',
        ]);

        $this->postJson("/api/v1/bookings/{$booking->id}/refund", ['reason' => 'again'])
            ->assertStatus(422);
    }

    public function test_cannot_refund_other_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $other->id, 'starts_at' => now()->addDays(5)]);
        $this->actingAsPlayer();

        $this->postJson("/api/v1/bookings/{$booking->id}/refund", ['reason' => 'not mine'])
            ->assertStatus(403);
    }

    public function test_pending_refund_blocks_new_request(): void
    {
        $user = $this->actingAsPlayer();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'starts_at' => now()->addDays(7),
        ]);
        RefundRequest::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'requested_amount' => 30000,
            'reason' => 'first',
            'refund_method' => 'wallet',
            'status' => 'pending_review',
        ]);

        $this->postJson("/api/v1/bookings/{$booking->id}/refund", ['reason' => 'second'])
            ->assertStatus(422);
    }
}
