<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_user_can_be_assigned_player_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $this->assertTrue($user->hasRole('player'));
    }

    public function test_factory_user_can_be_assigned_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_factory_user_can_be_assigned_club_manager_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('club_manager');

        $this->assertTrue($user->hasRole('club_manager'));
    }

    public function test_player_does_not_have_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_registered_user_has_player_spatie_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test Player',
            'phone_number'          => '+963944200001',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $user = User::where('phone_number', '+963944200001')->first();
        $this->assertTrue($user->hasRole('player'));
    }

    public function test_assigned_roles_use_web_guard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $role = $user->roles->first();
        $this->assertSame('web', $role->guard_name);
    }
}
