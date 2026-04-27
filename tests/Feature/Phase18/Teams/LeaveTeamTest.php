<?php

namespace Tests\Feature\Phase18\Teams;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveTeamTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeTeam(User $captain): Team
    {
        $categoryId = DB::table('venue_categories')->insertGetId([
            'slug' => 'cat-'.uniqid(),
            'name' => json_encode(['ar' => 'فئة', 'en' => 'Category']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Team::create([
            'name' => 'Test Team',
            'type' => 'casual',
            'sport_category_id' => $categoryId,
            'captain_id' => $captain->id,
            'max_members' => 10,
        ]);
    }

    public function test_member_can_leave_team(): void
    {
        $captain = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->makeTeam($captain);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'active', 'joined_at' => now()]);
        TeamMember::where(['team_id' => $team->id, 'user_id' => $captain->id])->update(['role' => 'captain', 'status' => 'active']);

        $this->actingAs($member, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}/leave")
            ->assertOk();

        $this->assertSame('left', TeamMember::where(['team_id' => $team->id, 'user_id' => $member->id])->value('status'));
    }

    public function test_only_admin_cannot_leave(): void
    {
        $captain = User::factory()->create();
        $team = $this->makeTeam($captain);
        $row = TeamMember::where(['team_id' => $team->id, 'user_id' => $captain->id])->first();
        $this->assertNotNull($row, 'booted hook should create captain TeamMember');
        $row->update(['role' => 'captain', 'status' => 'active']);

        $response = $this->actingAs($captain, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}/leave");

        $response->assertStatus(422);
    }

    public function test_non_member_returns_404(): void
    {
        $captain = User::factory()->create();
        $stranger = User::factory()->create();
        $team = $this->makeTeam($captain);

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/teams/{$team->id}/leave")
            ->assertStatus(404);
    }
}
