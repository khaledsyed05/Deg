<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class KickMemberTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeTeamWithMember(): array
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        $member = User::factory()->create();
        $member->assignRole('player');
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $team->increment('total_members');

        return [$captain, $team, $member];
    }

    public function test_captain_can_kick_member(): void
    {
        [$captain, $team, $member] = $this->makeTeamWithMember();

        $response = $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $member->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.team_id', $team->id)
            ->assertJsonPath('data.member_id', $member->id);

        $this->assertSame(
            'removed',
            TeamMember::where(['team_id' => $team->id, 'user_id' => $member->id])->value('status'),
        );
    }

    public function test_captain_cannot_kick_self(): void
    {
        [$captain, $team] = $this->makeTeamWithMember();

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $captain->id,
            ])
            ->assertStatus(422);
    }

    public function test_member_cannot_kick_anyone(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();
        $other = User::factory()->create();
        $other->assignRole('player');
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $other->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $other->id,
            ])
            ->assertForbidden();
    }

    public function test_stranger_cannot_kick(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();
        $stranger = User::factory()->create();
        $stranger->assignRole('player');

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $member->id,
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_kick(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();

        $this->postJson("/api/v1/teams/{$team->id}/kick", [
            'member_id' => $member->id,
        ])->assertUnauthorized();
    }

    public function test_kick_non_member_returns_422(): void
    {
        [$captain, $team] = $this->makeTeamWithMember();
        $randomUser = User::factory()->create();

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $randomUser->id,
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_kick_member(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/kick", [
                'member_id' => $member->id,
            ])
            ->assertOk();
    }

    public function test_kick_after_team_deleted_returns_404(): void
    {
        [$captain, $team, $member] = $this->makeTeamWithMember();
        $teamId = $team->id;
        $team->delete();

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$teamId}/kick", [
                'member_id' => $member->id,
            ])
            ->assertNotFound();
    }
}
