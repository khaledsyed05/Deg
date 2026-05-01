<?php

namespace Tests\Feature\Admin;

use App\Models\App\AppVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppVersionAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
    }

    public function test_non_admin_cannot_list_versions(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/admin/v1/app-versions')
            ->assertStatus(403);
    }

    public function test_admin_can_list_versions(): void
    {
        AppVersion::create([
            'platform' => 'ios',
            'version' => '1.0.0',
            'build_number' => 100,
            'is_active' => true,
            'released_at' => today(),
        ]);
        AppVersion::create([
            'platform' => 'android',
            'version' => '1.0.0',
            'build_number' => 100,
            'is_active' => true,
            'released_at' => today(),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/v1/app-versions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'platform', 'version', 'build_number', 'is_active', 'is_force_update']],
                    'meta' => ['current_page', 'last_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_admin_can_filter_versions_by_platform(): void
    {
        AppVersion::create(['platform' => 'ios', 'version' => '1.0.0', 'build_number' => 100, 'is_active' => true, 'released_at' => today()]);
        AppVersion::create(['platform' => 'android', 'version' => '1.0.0', 'build_number' => 100, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/v1/app-versions?platform=ios')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.platform', 'ios');
    }

    public function test_admin_can_create_version(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/v1/app-versions', [
                'platform' => 'android',
                'version' => '2.0.0',
                'build_number' => 200,
                'is_force_update' => true,
                'is_active' => true,
                'release_notes' => 'New features',
                'release_notes_ar' => 'ميزات جديدة',
                'released_at' => '2026-04-28',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.version', '2.0.0')
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.is_force_update', true);

        $this->assertDatabaseHas('app_versions', [
            'platform' => 'android',
            'version' => '2.0.0',
            'build_number' => 200,
        ]);
    }

    public function test_create_version_fails_with_duplicate_platform_version(): void
    {
        AppVersion::create(['platform' => 'ios', 'version' => '1.0.0', 'build_number' => 100, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/v1/app-versions', [
                'platform' => 'ios',
                'version' => '1.0.0',
                'build_number' => 101,
                'released_at' => '2026-04-28',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_show_version(): void
    {
        $version = AppVersion::create(['platform' => 'ios', 'version' => '1.0.0', 'build_number' => 100, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/v1/app-versions/{$version->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $version->id)
            ->assertJsonPath('data.version', '1.0.0');
    }

    public function test_admin_can_update_version(): void
    {
        $version = AppVersion::create(['platform' => 'android', 'version' => '1.0.0', 'build_number' => 100, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/v1/app-versions/{$version->id}", [
                'build_number' => 110,
                'is_force_update' => true,
                'release_notes' => 'Critical fix',
            ])
            ->assertOk()
            ->assertJsonPath('data.build_number', 110)
            ->assertJsonPath('data.is_force_update', true);

        $this->assertDatabaseHas('app_versions', ['id' => $version->id, 'build_number' => 110]);
    }

    public function test_admin_can_delete_version(): void
    {
        $version = AppVersion::create(['platform' => 'ios', 'version' => '3.0.0', 'build_number' => 300, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/v1/app-versions/{$version->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('app_versions', ['id' => $version->id]);
    }

    public function test_admin_can_activate_and_deactivate_version(): void
    {
        $version = AppVersion::create(['platform' => 'ios', 'version' => '1.1.0', 'build_number' => 110, 'is_active' => true, 'released_at' => today()]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/v1/app-versions/{$version->id}/activate", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('app_versions', ['id' => $version->id, 'is_active' => false]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/v1/app-versions/{$version->id}/activate", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_public_version_endpoint_reflects_admin_changes(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/v1/app-versions', [
                'platform' => 'android',
                'version' => '5.0.0',
                'build_number' => 500,
                'is_force_update' => true,
                'is_active' => true,
                'released_at' => '2026-04-28',
            ])
            ->assertStatus(201);

        $this->withHeaders([
            'X-App-Platform' => 'android',
            'X-App-Version' => '4.0.0',
            'X-App-Build' => '400',
        ])->getJson('/api/v1/app/version')
            ->assertOk()
            ->assertJsonPath('data.current_version', '5.0.0')
            ->assertJsonPath('data.needs_update', true)
            ->assertJsonPath('data.force_update', true);
    }
}
