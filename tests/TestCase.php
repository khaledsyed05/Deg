<?php

namespace Tests;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        $this->artisan('db:seed', ['--class' => 'CommissionConfigSeeder']);
    }

    protected function authenticatedUser(string $role = 'player', array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsPlayer(array $attributes = []): User
    {
        $user = $this->authenticatedUser('player', $attributes);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    /**
     * @return array{0: User, 1: Club}
     */
    protected function actingAsClubManager(?Club $club = null, array $attributes = []): array
    {
        $club = $club ?? Club::factory()->create(['status' => 'active']);
        $user = $this->authenticatedUser('club_manager', $attributes);
        $club->update(['owner_id' => $user->id]);
        $club->managers()->attach($user->id);
        $this->actingAs($user, 'sanctum');

        return [$user, $club];
    }

    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = $this->authenticatedUser('admin', $attributes);
        $this->actingAs($user, 'sanctum');

        return $user;
    }
}
