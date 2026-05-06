<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeamPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeCaptainAndTeam(bool $isPublic = false): array
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');

        $team = Team::factory()->withCaptain($captain)->state(['is_public' => $isPublic])->create();

        return [$captain, $team];
    }

    private function makeMember(Team $team): User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }

    private function makeStranger(): User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    // ============================================================
    // view (public team)
    // ============================================================

    public function test_view_public_team_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam(isPublic: true);
        $this->assertTrue($captain->can('view', $team));
    }

    public function test_view_public_team_allows_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: true);
        $member = $this->makeMember($team);
        $this->assertTrue($member->can('view', $team));
    }

    public function test_view_public_team_allows_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: true);
        $this->assertTrue($this->makeStranger()->can('view', $team));
    }

    public function test_view_public_team_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: true);
        $this->assertTrue($this->makeAdmin()->can('view', $team));
    }

    // ============================================================
    // view (private team)
    // ============================================================

    public function test_view_private_team_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam(isPublic: false);
        $this->assertTrue($captain->can('view', $team));
    }

    public function test_view_private_team_allows_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: false);
        $member = $this->makeMember($team);
        $this->assertTrue($member->can('view', $team));
    }

    public function test_view_private_team_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: false);
        $this->assertFalse($this->makeStranger()->can('view', $team));
    }

    public function test_view_private_team_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam(isPublic: false);
        $this->assertTrue($this->makeAdmin()->can('view', $team));
    }

    // ============================================================
    // update
    // ============================================================

    public function test_update_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($captain->can('update', $team));
    }

    public function test_update_denies_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $member = $this->makeMember($team);
        $this->assertFalse($member->can('update', $team));
    }

    public function test_update_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeStranger()->can('update', $team));
    }

    public function test_update_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($this->makeAdmin()->can('update', $team));
    }

    // ============================================================
    // delete
    // ============================================================

    public function test_delete_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($captain->can('delete', $team));
    }

    public function test_delete_denies_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeMember($team)->can('delete', $team));
    }

    public function test_delete_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeStranger()->can('delete', $team));
    }

    public function test_delete_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($this->makeAdmin()->can('delete', $team));
    }

    // ============================================================
    // kick
    // ============================================================

    public function test_kick_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($captain->can('kick', $team));
    }

    public function test_kick_denies_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeMember($team)->can('kick', $team));
    }

    public function test_kick_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeStranger()->can('kick', $team));
    }

    public function test_kick_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($this->makeAdmin()->can('kick', $team));
    }

    // ============================================================
    // transferCaptain — captain-only by design (admin denied)
    // ============================================================

    public function test_transfer_captain_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($captain->can('transferCaptain', $team));
    }

    public function test_transfer_captain_denies_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeMember($team)->can('transferCaptain', $team));
    }

    public function test_transfer_captain_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeStranger()->can('transferCaptain', $team));
    }

    public function test_transfer_captain_denies_admin_who_is_not_captain(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeAdmin()->can('transferCaptain', $team));
    }

    // ============================================================
    // invite
    // ============================================================

    public function test_invite_allows_captain(): void
    {
        [$captain, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($captain->can('invite', $team));
    }

    public function test_invite_denies_member(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeMember($team)->can('invite', $team));
    }

    public function test_invite_denies_stranger(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertFalse($this->makeStranger()->can('invite', $team));
    }

    public function test_invite_allows_admin(): void
    {
        [, $team] = $this->makeCaptainAndTeam();
        $this->assertTrue($this->makeAdmin()->can('invite', $team));
    }
}
