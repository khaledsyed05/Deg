<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GenerateInviteTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeCaptainAndTeam(): array
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $team = Team::factory()->withCaptain($captain)->create();

        return [$captain, $team];
    }

    public function test_captain_can_generate_invite(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $response = $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", []);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'team_id', 'code', 'created_by', 'expires_at', 'max_uses', 'uses_count', 'is_active'],
            ])
            ->assertJsonPath('data.team_id', $team->id)
            ->assertJsonPath('data.created_by', $captain->id)
            ->assertJsonPath('data.uses_count', 0)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseCount('team_invites', 1);
    }

    public function test_captain_can_generate_invite_with_options(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $response = $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", [
                'expires_at' => now()->addDays(7)->toIso8601String(),
                'max_uses' => 5,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.max_uses', 5);

        $this->assertNotNull(TeamInvite::query()->value('expires_at'));
    }

    public function test_member_cannot_generate_invite(): void
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
            ->postJson("/api/v1/teams/{$team->id}/invite", [])
            ->assertForbidden();
    }

    public function test_stranger_cannot_generate_invite(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $stranger = User::factory()->create();
        $stranger->assignRole('player');

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", [])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_generate_invite(): void
    {
        [, $team] = $this->makeCaptainAndTeam();

        $this->postJson("/api/v1/teams/{$team->id}/invite", [])->assertUnauthorized();
    }

    public function test_admin_can_generate_invite(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", [])
            ->assertCreated();
    }

    public function test_invite_code_is_unique(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        $codes = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($captain, 'sanctum')
                ->postJson("/api/v1/teams/{$team->id}/invite", []);
            $codes[] = $response->json('data.code');
        }

        $this->assertCount(3, array_unique($codes));
    }

    public function test_too_many_active_invites_returns_422(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        for ($i = 0; $i < 5; $i++) {
            TeamInvite::factory()->create([
                'team_id' => $team->id,
                'created_by' => $captain->id,
            ]);
        }

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", [])
            ->assertStatus(422);
    }

    public function test_expired_invites_dont_count_toward_limit(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();

        for ($i = 0; $i < 5; $i++) {
            TeamInvite::factory()->expired()->create([
                'team_id' => $team->id,
                'created_by' => $captain->id,
            ]);
        }

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/invite", [])
            ->assertCreated();
    }
}
