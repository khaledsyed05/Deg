<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MyBookingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/bookings')->assertUnauthorized();
    }

    public function test_returns_only_the_authenticated_users_bookings(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Booking::factory()->count(2)->create(['user_id' => $user->id]);
        Booking::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_collection_when_user_has_no_bookings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_response_includes_booking_fields(): void
    {
        $user    = User::factory()->create();
        Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bookings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'booking_code',
                    'status',
                    'booking_date',
                    'start_time',
                    'end_time',
                    'total_price',
                ]],
            ]);
    }
}
