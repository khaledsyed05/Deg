<?php

namespace Tests\Feature\App;

use App\Models\App\AppConfig;
use App\Models\App\AppVersion;
use App\Models\App\FeatureFlag;
use App\Models\App\MaintenanceWindow;
use App\Models\User;
use Tests\TestCase;

class AppMetadataTest extends TestCase
{
    public function test_version_check_with_no_records(): void
    {
        $this->withHeaders(['X-App-Platform' => 'ios', 'X-App-Build' => 100])
            ->getJson('/api/v1/app/version')
            ->assertStatus(200)
            ->assertJsonPath('data.needs_update', false);
    }

    public function test_version_needs_update_when_below_latest(): void
    {
        AppVersion::create([
            'platform' => 'ios', 'version' => '1.5.0', 'build_number' => 150,
            'is_active' => true, 'released_at' => today(),
        ]);
        $this->withHeaders(['X-App-Platform' => 'ios', 'X-App-Build' => 100])
            ->getJson('/api/v1/app/version')
            ->assertJsonPath('data.needs_update', true)
            ->assertJsonPath('data.force_update', false);
    }

    public function test_force_update_when_below_minimum(): void
    {
        AppVersion::create([
            'platform' => 'android', 'version' => '1.0.0', 'build_number' => 100,
            'is_active' => true, 'is_force_update' => true, 'released_at' => today(),
        ]);
        AppVersion::create([
            'platform' => 'android', 'version' => '1.5.0', 'build_number' => 150,
            'is_active' => true, 'released_at' => today(),
        ]);
        $this->withHeaders(['X-App-Platform' => 'android', 'X-App-Build' => 50])
            ->getJson('/api/v1/app/version')
            ->assertJsonPath('data.force_update', true);
    }

    public function test_invalid_platform_returns_422(): void
    {
        $this->withHeaders(['X-App-Platform' => 'web'])
            ->getJson('/api/v1/app/version')
            ->assertStatus(422);
    }

    public function test_feature_flags_returns_all_keys(): void
    {
        FeatureFlag::create(['key' => 'a', 'name' => 'A', 'is_enabled' => true]);
        FeatureFlag::create(['key' => 'b', 'name' => 'B', 'is_enabled' => false]);
        $this->getJson('/api/v1/app/feature-flags')
            ->assertStatus(200)
            ->assertJsonPath('data.a', true)
            ->assertJsonPath('data.b', false);
    }

    public function test_feature_flag_user_id_targeting(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        FeatureFlag::create([
            'key' => 'beta',
            'name' => 'Beta',
            'is_enabled' => true,
            'targeting' => ['user_ids' => [$user->id]],
        ]);
        $flag = FeatureFlag::where('key', 'beta')->first();
        $this->assertTrue($flag->isEnabledFor($user));
        $this->assertFalse($flag->isEnabledFor($other));
    }

    public function test_feature_flag_percentage_rollout_is_deterministic(): void
    {
        $user = User::factory()->create();
        FeatureFlag::create([
            'key' => 'rollout', 'name' => 'Rollout', 'is_enabled' => true,
            'targeting' => ['percentage' => 100],
        ]);
        $flag = FeatureFlag::where('key', 'rollout')->first();
        $this->assertTrue($flag->isEnabledFor($user));

        $flag->update(['targeting' => ['percentage' => 0]]);
        $this->assertFalse($flag->fresh()->isEnabledFor($user));
    }

    public function test_maintenance_inactive_when_no_window(): void
    {
        $this->getJson('/api/v1/app/maintenance')
            ->assertStatus(200)
            ->assertJsonPath('data.is_under_maintenance', false);
    }

    public function test_maintenance_active_window(): void
    {
        MaintenanceWindow::create([
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'message_ar' => 'صيانة',
        ]);
        $this->getJson('/api/v1/app/maintenance')
            ->assertStatus(200)
            ->assertJsonPath('data.is_under_maintenance', true)
            ->assertJsonPath('data.message_ar', 'صيانة');
    }

    public function test_maintenance_blocks_non_app_routes(): void
    {
        MaintenanceWindow::create([
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'message' => 'down',
        ]);
        // /app/* routes still accessible
        $this->getJson('/api/v1/app/maintenance')->assertStatus(200);
        // other api routes return 503
        $this->getJson('/api/v1/categories')->assertStatus(503);
    }

    public function test_public_config_excludes_private_keys(): void
    {
        AppConfig::create(['key' => 'public_one', 'value' => 'v1', 'is_public' => true]);
        AppConfig::create(['key' => 'private_one', 'value' => 'secret', 'is_public' => false]);
        $this->getJson('/api/v1/app/config')
            ->assertStatus(200)
            ->assertJsonPath('data.public_one', 'v1')
            ->assertJsonMissing(['private_one']);
    }

    public function test_health_endpoint_reports_ok(): void
    {
        $this->getJson('/api/v1/app/health')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'healthy')
            ->assertJsonPath('data.checks.database', 'ok');
    }
}
