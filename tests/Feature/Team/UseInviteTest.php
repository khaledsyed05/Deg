<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UseInviteTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeInvite(array $inviteAttrs = [], array $teamAttrs = []): TeamInvite
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->state($teamAttrs)->create();

        return TeamInvite::factory()->create(array_merge([
            'team_id' => $team->id,
            'created_by' => $captain->id,
        ], $inviteAttrs));
    }

    public function test_authenticated_user_can_join_via_invite(): void
    {
        $invite = $this->makeInvite();
        $newMember = User::factory()->create();
        $newMember->assignRole('player');

        $response = $this->actingAs($newMember, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}");

        $response->assertOk()
            ->assertJsonPath('data.team_id', $invite->team_id)
            ->assertJsonPath('data.already_member', false);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $invite->team_id,
            'user_id' => $newMember->id,
            'status' => 'active',
        ]);

        $this->assertSame(1, TeamInvite::find($invite->id)->uses_count);
    }

    public function test_expired_invite_returns_410(): void
    {
        $invite = TeamInvite::factory()->expired()->create([
            'team_id' => Team::factory()->create()->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('player');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}")
            ->assertStatus(410);
    }

    public function test_used_up_invite_returns_410(): void
    {
        $invite = TeamInvite::factory()->usedUp()->create([
            'team_id' => Team::factory()->create()->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $user = User::factory()->create();
        $user->assignRole('player');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}")
            ->assertStatus(410);
    }

    public function test_already_member_is_idempotent(): void
    {
        $invite = $this->makeInvite();
        $member = User::factory()->create();
        $member->assignRole('player');
        TeamMember::create([
            'team_id' => $invite->team_id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}");

        $response->assertOk()
            ->assertJsonPath('data.already_member', true)
            ->assertJsonPath('data.team_id', $invite->team_id);

        // uses_count should NOT increment for an already-member request
        $this->assertSame(0, TeamInvite::find($invite->id)->uses_count);
    }

    public function test_team_full_returns_422(): void
    {
        $invite = $this->makeInvite([], ['max_members' => 2]);

        // Captain already counts; add one more to fill the team.
        $filler = User::factory()->create();
        $filler->assignRole('player');
        TeamMember::create([
            'team_id' => $invite->team_id,
            'user_id' => $filler->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        Team::find($invite->team_id)->increment('total_members');

        $newUser = User::factory()->create();
        $newUser->assignRole('player');

        $this->actingAs($newUser, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}")
            ->assertStatus(422);
    }

    public function test_code_not_found_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/teams/invite/NOSUCHCODE')
            ->assertNotFound();
    }

    public function test_unauthenticated_cannot_use_invite(): void
    {
        $invite = $this->makeInvite();

        $this->getJson("/api/v1/teams/invite/{$invite->code}")
            ->assertUnauthorized();
    }

    public function test_invite_with_uses_remaining_works_after_first_use(): void
    {
        $invite = $this->makeInvite(['max_uses' => 2, 'uses_count' => 1]);
        $newMember = User::factory()->create();
        $newMember->assignRole('player');

        $this->actingAs($newMember, 'sanctum')
            ->getJson("/api/v1/teams/invite/{$invite->code}")
            ->assertOk();

        $this->assertSame(2, TeamInvite::find($invite->id)->uses_count);
    }
}
