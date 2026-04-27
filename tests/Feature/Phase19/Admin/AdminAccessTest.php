<?php

namespace Tests\Feature\Phase19\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    }

    public function test_non_admin_blocked_from_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/v1/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['total_users', 'total_bookings', 'total_revenue'],
            ]);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        User::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/v1/users')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 4);
    }

    public function test_admin_can_ban_and_unban_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/v1/users/{$target->id}/ban", ['reason' => 'spam'])
            ->assertOk();

        $this->assertSame('blocked', $target->fresh()->account_status->value ?? $target->fresh()->account_status);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/v1/users/{$target->id}/unban")
            ->assertOk();

        $this->assertSame('active', $target->fresh()->account_status->value ?? $target->fresh()->account_status);
    }
}
