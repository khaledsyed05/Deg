<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpdateTeamTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeCaptainAndTeam(): array
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        return [$captain, $team];
    }

    public function test_captain_can_update_team(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $response = $this->actingAs($captain, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}", [
                'name' => 'Updated Team Name',
                'description' => 'New description',
                'is_public' => true,
                'max_members' => 15,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Team Name')
            ->assertJsonPath('data.description', 'New description')
            ->assertJsonPath('data.is_public', true)
            ->assertJsonPath('data.max_members', 15);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team Name',
            'is_public' => true,
            'max_members' => 15,
        ]);
    }

    public function test_member_cannot_update_team(): void
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
            ->putJson("/api/v1/teams/{$team->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_other_authenticated_user_cannot_update_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $stranger = User::factory()->create();
        $stranger->assignRole('player');

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_update_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();

        $this->putJson("/api/v1/teams/{$team->id}", ['name' => 'Hacked'])
            ->assertUnauthorized();
    }

    public function test_admin_can_update_team(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}", ['name' => 'Admin Update'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Update');
    }

    public function test_update_team_validates_name_length(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $this->actingAs($captain, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}", ['name' => 'ab'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_team_returns_404_for_missing_team(): void
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');

        $this->actingAs($captain, 'sanctum')
            ->putJson('/api/v1/teams/999999', ['name' => 'Some Valid Name'])
            ->assertNotFound();
    }
}
