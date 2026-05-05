<?php

namespace Tests\Feature\MobileEnvelope;

use App\Models\Booking;
use App\Models\Venue;
use Tests\MobileIntegrationTest;

/**
 * Phase 3 — Venue Detail + Booking (9 endpoints).
 *
 * - GET /venues/{slug}
 * - GET /venues/{slug}/availability
 * - POST /bookings/check-availability
 * - POST /bookings/calculate-price
 * - POST /bookings
 * - GET /venues/{slug}/reviews
 * - POST /reviews
 * - PUT /bookings/{id}/cancel
 * - PUT /bookings/{id}/reschedule
 */
class Phase03BookingTest extends MobileIntegrationTest
{
    public function test_get_venue_show_returns_envelope(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->getJson("/api/v1/venues/{$venue->slug}");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_venue_availability_returns_envelope(): void
    {
        $venue = Venue::factory()->create();
        $date = now()->addDay()->toDateString();

        $response = $this->getJson("/api/v1/venues/{$venue->slug}/availability?date={$date}");

        $this->assertEnvelope($response);
    }

    public function test_post_bookings_check_availability_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/bookings/check-availability', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_bookings_calculate_price_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/bookings/calculate-price', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_post_bookings_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/bookings', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_get_venue_reviews_returns_envelope(): void
    {
        $venue = Venue::factory()->create();

        $response = $this->getJson("/api/v1/venues/{$venue->slug}/reviews");

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_post_reviews_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/reviews', []);

        $this->assertErrorEnvelope($response, 422);
    }

    public function test_put_booking_cancel_returns_envelope(): void
    {
        $user = $this->actingAsRole('player');
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}/cancel");

        $this->assertEnvelope($response);
    }

    public function test_put_booking_reschedule_returns_envelope(): void
    {
        $user = $this->actingAsRole('player');
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}/reschedule", []);

        $this->assertEnvelope($response);
    }
}
