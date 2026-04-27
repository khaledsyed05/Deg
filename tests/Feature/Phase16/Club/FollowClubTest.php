<?php

namespace Tests\Feature\Phase16\Club;

use App\Models\Club;
use App\Models\ClubFollower;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FollowClubTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_follow_returns_401(): void
    {
        $club = Club::factory()->create();

        $this->postJson("/api/v1/clubs/{$club->id}/follow")
            ->assertUnauthorized();
    }

    public function test_user_can_follow_a_club(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/clubs/{$club->id}/follow")
            ->assertCreated()
            ->assertJsonPath('data.club_id', $club->id);

        $this->assertDatabaseHas('club_followers', [
            'user_id' => $user->id,
            'club_id' => $club->id,
        ]);

        $this->assertSame(1, (int) $club->fresh()->followers_count);
    }

    public function test_following_twice_returns_422(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();
        ClubFollower::create(['user_id' => $user->id, 'club_id' => $club->id]);
        $club->increment('followers_count');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/clubs/{$club->id}/follow")
            ->assertStatus(422);
    }

    public function test_user_can_unfollow_a_club(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();
        ClubFollower::create(['user_id' => $user->id, 'club_id' => $club->id]);
        $club->increment('followers_count');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/clubs/{$club->id}/follow")
            ->assertOk();

        $this->assertDatabaseMissing('club_followers', [
            'user_id' => $user->id,
            'club_id' => $club->id,
        ]);

        $this->assertSame(0, (int) $club->fresh()->followers_count);
    }

    public function test_unfollow_when_not_following_returns_422(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/clubs/{$club->id}/follow")
            ->assertStatus(422);
    }

    public function test_followed_clubs_listing(): void
    {
        $user = User::factory()->create();
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        ClubFollower::create(['user_id' => $user->id, 'club_id' => $clubA->id]);
        ClubFollower::create(['user_id' => $user->id, 'club_id' => $clubB->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/clubs/followed')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }
}
