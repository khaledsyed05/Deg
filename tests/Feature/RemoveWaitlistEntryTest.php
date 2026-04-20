<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VenueWaitlist;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RemoveWaitlistEntryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $entry = VenueWaitlist::factory()->create();

        $this->deleteJson('/api/v1/waitlist/' . $entry->id)->assertUnauthorized();
    }

    public function test_authenticated_owner_can_remove_their_own_waitlist_entry(): void
    {
        $user  = User::factory()->create();
        $entry = VenueWaitlist::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/waitlist/' . $entry->id)
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_entry_is_deleted_from_database(): void
    {
        $user  = User::factory()->create();
        $entry = VenueWaitlist::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/waitlist/' . $entry->id)
            ->assertOk();

        $this->assertDatabaseMissing('venue_waitlist', ['id' => $entry->id]);
    }

    public function test_user_cannot_remove_another_users_waitlist_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = VenueWaitlist::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson('/api/v1/waitlist/' . $entry->id)
            ->assertForbidden();

        $this->assertDatabaseHas('venue_waitlist', ['id' => $entry->id]);
    }

    public function test_nonexistent_waitlist_entry_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/waitlist/99999')
            ->assertNotFound();
    }
}
