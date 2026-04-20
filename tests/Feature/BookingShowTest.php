<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $booking = Booking::factory()->create();

        $this->getJson('/api/v1/bookings/' . $booking->id)->assertUnauthorized();
    }

    public function test_authenticated_owner_can_fetch_their_own_booking(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings/' . $booking->id)
            ->assertOk()
            ->assertJsonPath('data.id', $booking->id);
    }

    public function test_authenticated_user_cannot_fetch_another_users_booking(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/bookings/' . $booking->id)
            ->assertForbidden();
    }

    public function test_nonexistent_booking_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings/99999')
            ->assertNotFound();
    }

    public function test_response_includes_booking_detail_fields(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings/' . $booking->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'booking_code',
                    'status',
                    'booking_date',
                    'start_time',
                    'end_time',
                    'total_price',
                    'deposit_amount',
                ],
            ]);
    }
}
