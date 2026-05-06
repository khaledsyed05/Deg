<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeleteTeamTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeCaptainAndTeam(): array
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        return [$captain, $team];
    }

    public function test_captain_can_delete_team(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $response = $this->actingAs($captain, 'sanctum')
            ->deleteJson("/api/v1/teams/{$team->id}");

        $response->assertOk()->assertJsonPath('data.team_id', $team->id);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_member_cannot_delete_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $member = User::factory()->create();
        $member->assignRole('player');
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    public function test_stranger_cannot_delete_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $stranger = User::factory()->create();
        $stranger->assignRole('player');

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_delete_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();

        $this->deleteJson("/api/v1/teams/{$team->id}")->assertUnauthorized();
    }

    public function test_admin_can_delete_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_delete_team_cascades_members_and_invites(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $member = User::factory()->create();
        $member->assignRole('player');
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        TeamInvite::factory()->create([
            'team_id' => $team->id,
            'created_by' => $captain->id,
        ]);

        $this->actingAs($captain, 'sanctum')
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('team_members', ['team_id' => $team->id]);
        $this->assertDatabaseMissing('team_invites', ['team_id' => $team->id]);
    }

    public function test_delete_team_returns_404_for_missing_team(): void
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');

        $this->actingAs($captain, 'sanctum')
            ->deleteJson('/api/v1/teams/999999')
            ->assertNotFound();
    }
}
