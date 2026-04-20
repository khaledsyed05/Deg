<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use App\Models\VenueWaitlist;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WaitlistEntryShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_waitlist_index_returns_entries_with_expected_structure(): void
    {
        $user  = User::factory()->create();
        VenueWaitlist::factory()->create([
            'user_id'  => $user->id,
            'venue_id' => $this->venue->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/waitlist')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'venue_id', 'booking_date', 'start_time', 'duration_minutes'],
                ],
            ]);
    }

    public function test_joining_waitlist_twice_for_same_slot_returns_422(): void
    {
        $user = User::factory()->create();

        $payload = [
            'venue_id'         => $this->venue->id,
            'preferred_date'   => now()->addDays(3)->toDateString(),
            'preferred_time'   => '18:00',
            'duration_minutes' => 60,
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', $payload)
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', $payload)
            ->assertUnprocessable();
    }

    public function test_past_preferred_date_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'       => $this->venue->id,
                'preferred_date' => now()->subDay()->toDateString(),
                'preferred_time' => '18:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preferred_date']);
    }

    public function test_missing_preferred_time_is_accepted_since_it_is_nullable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/waitlist', [
                'venue_id'       => $this->venue->id,
                'preferred_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertCreated();
    }
}
