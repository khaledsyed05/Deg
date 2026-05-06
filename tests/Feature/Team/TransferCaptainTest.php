<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferCaptainTest extends TestCase
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

        return [$captain, $team, $member];
    }

    public function test_captain_can_transfer_to_member(): void
    {
        [$captain, $team, $member] = $this->makeTeamWithMember();

        $response = $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
                'new_captain_id' => $member->id,
            ]);

        $response->assertOk()->assertJsonPath('data.captain_id', $member->id);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'captain_id' => $member->id,
        ]);

        $this->assertSame(
            'captain',
            DB::table('team_members')
                ->where(['team_id' => $team->id, 'user_id' => $member->id])
                ->value('role'),
        );

        $this->assertSame(
            'member',
            DB::table('team_members')
                ->where(['team_id' => $team->id, 'user_id' => $captain->id])
                ->value('role'),
        );
    }

    public function test_transfer_to_non_member_returns_422(): void
    {
        [$captain, $team] = $this->makeTeamWithMember();
        $stranger = User::factory()->create();

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
                'new_captain_id' => $stranger->id,
            ])
            ->assertStatus(422);
    }

    public function test_transfer_to_self_is_idempotent(): void
    {
        [$captain, $team] = $this->makeTeamWithMember();

        $response = $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
                'new_captain_id' => $captain->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'captain_id' => $captain->id,
        ]);
    }

    public function test_member_cannot_transfer_captain(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();
        $other = User::factory()->create();
        $other->assignRole('player');

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
                'new_captain_id' => $other->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_who_is_not_captain_cannot_transfer(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
                'new_captain_id' => $member->id,
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_transfer(): void
    {
        [, $team, $member] = $this->makeTeamWithMember();

        $this->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
            'new_captain_id' => $member->id,
        ])->assertUnauthorized();
    }

    public function test_transfer_validates_new_captain_id(): void
    {
        [$captain, $team] = $this->makeTeamWithMember();

        $this->actingAs($captain, 'sanctum')
            ->postJson("/api/v1/teams/{$team->id}/transfer-captain", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_captain_id']);
    }
}
