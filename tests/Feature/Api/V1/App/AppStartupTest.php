<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\App\MaintenanceWindow;
use App\Models\AppEnvironment;
use App\Models\AppPlatform;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppStartupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function seedAndroid(array $overrides = []): AppPlatform
    {
        $platform = AppPlatform::updateOrCreate(
            ['platform_key' => 'android'],
            array_merge([
                'name' => ['en' => 'Android', 'ar' => 'أندرويد'],
                'latest_version' => '2.0.0',
                'minimum_required_version' => '1.5.0',
                'store_url' => 'https://play.google.com/store/test',
                'direct_apk_url' => null,
                'direct_apk_enabled' => 0,
                'is_active' => 1,
                'order_column' => 1,
            ], $overrides),
        );

        AppEnvironment::updateOrCreate(
            ['platform_id' => $platform->id, 'name' => 'live'],
            ['base_url' => 'https://api.test/v1', 'is_active' => 1],
        );

        return $platform->fresh('activeEnvironment');
    }

    public function test_returns_no_update_when_app_version_equals_latest(): void
    {
        $this->seedAndroid();

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '2.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.base_url', 'https://api.test/v1')
            ->assertJsonPath('data.update.is_required', false)
            ->assertJsonPath('data.update.is_optional', false);
    }

    public function test_returns_optional_when_between_minimum_and_latest(): void
    {
        $this->seedAndroid();

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '1.7.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.update.is_required', false)
            ->assertJsonPath('data.update.is_optional', true);
    }

    public function test_returns_required_when_below_minimum(): void
    {
        $this->seedAndroid();

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.update.is_required', true)
            ->assertJsonPath('data.update.is_optional', false);
    }

    public function test_returns_direct_apk_url_when_toggle_is_on_for_android(): void
    {
        $this->seedAndroid([
            'direct_apk_url' => 'https://yallaehjez.com/releases/app-2.0.apk',
            'direct_apk_enabled' => 1,
        ]);

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.update.store_url', 'https://yallaehjez.com/releases/app-2.0.apk');
    }

    public function test_returns_maintenance_mode_when_active_window_exists(): void
    {
        $this->seedAndroid();
        MaintenanceWindow::create([
            'is_active' => true,
            'message' => 'Brief downtime',
            'message_ar' => 'انقطاع قصير',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '2.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.app_settings.maintenance_mode', true)
            ->assertJsonPath('data.app_settings.maintenance_message.en', 'Brief downtime')
            ->assertJsonPath('data.app_settings.maintenance_message.ar', 'انقطاع قصير');
    }

    public function test_rejects_malformed_version(): void
    {
        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => 'not-a-version',
        ])->assertStatus(422);
    }

    public function test_rejects_unknown_platform(): void
    {
        $this->postJson('/api/v1/app/startup', [
            'platform' => 'windows',
            'app_version' => '1.0.0',
        ])->assertStatus(422);
    }

    public function test_returns_fallback_when_platform_inactive(): void
    {
        AppPlatform::updateOrCreate(
            ['platform_key' => 'android'],
            [
                'name' => ['en' => 'Android', 'ar' => 'أندرويد'],
                'latest_version' => '1.0.0',
                'minimum_required_version' => '1.0.0',
                'store_url' => 'https://play.google.com/store/test',
                'is_active' => 0,
            ],
        );

        $this->postJson('/api/v1/app/startup', [
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.update.is_required', false)
            ->assertJsonPath('data.update.is_optional', false);
    }
}
