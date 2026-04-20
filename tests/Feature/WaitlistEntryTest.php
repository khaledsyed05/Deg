<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueWaitlist;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WaitlistEntryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/v1/waitlist', [
            'venue_id'      => $this->venue->id,
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '18:00',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_join_waitlist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'         => $this->venue->id,
                'preferred_date'   => now()->addDays(3)->toDateString(),
                'preferred_time'   => '18:00',
                'duration_minutes' => 60,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('venue_waitlist', [
            'venue_id' => $this->venue->id,
            'user_id'  => $user->id,
        ]);
    }

    public function test_missing_venue_id_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'preferred_date' => now()->addDays(3)->toDateString(),
                'preferred_time' => '18:00',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_id']);
    }

    public function test_nonexistent_venue_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'       => 99999,
                'preferred_date' => now()->addDays(3)->toDateString(),
                'preferred_time' => '18:00',
            ])->assertUnprocessable();
    }

    public function test_missing_preferred_date_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'       => $this->venue->id,
                'preferred_time' => '18:00',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['preferred_date']);
    }

    public function test_past_preferred_date_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'       => $this->venue->id,
                'preferred_date' => now()->subDay()->toDateString(),
                'preferred_time' => '18:00',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['preferred_date']);
    }
}
