<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VenueWaitlist;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MyWaitlistTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/waitlist')->assertUnauthorized();
    }

    public function test_returns_only_the_authenticated_users_waitlist_entries(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        VenueWaitlist::factory()->count(2)->create(['user_id' => $user->id]);
        VenueWaitlist::factory()->count(3)->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/waitlist')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_collection_when_user_has_no_waitlist_entries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/waitlist')
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
