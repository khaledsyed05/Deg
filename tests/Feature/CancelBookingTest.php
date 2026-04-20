<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CancelBookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $booking = Booking::factory()->create();

        $this->postJson('/api/v1/bookings/' . $booking->id . '/cancel')
            ->assertUnauthorized();
    }

    public function test_owner_can_cancel_a_confirmed_booking(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status'  => BookingStatus::Confirmed,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings/' . $booking->id . '/cancel', ['confirmed' => true])
            ->assertOk();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_user_cannot_cancel_another_users_booking(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'status'  => BookingStatus::Confirmed,
        ]);

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/bookings/' . $booking->id . '/cancel', ['confirmed' => true])
            ->assertForbidden();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_cancelling_a_completed_booking_returns_422(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status'  => BookingStatus::Completed,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings/' . $booking->id . '/cancel', ['confirmed' => true])
            ->assertUnprocessable();

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_cancelling_a_cancelled_booking_returns_422(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status'  => BookingStatus::Cancelled,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings/' . $booking->id . '/cancel', ['confirmed' => true])
            ->assertUnprocessable();
    }

    public function test_nonexistent_booking_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings/99999/cancel', ['confirmed' => true])
            ->assertNotFound();
    }

    public function test_missing_confirmation_returns_422(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings/' . $booking->id . '/cancel')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['confirmed']);
    }
}
