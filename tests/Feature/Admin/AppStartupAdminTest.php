<?php

namespace Tests\Feature\Admin;

use App\Models\App\MaintenanceWindow;
use App\Models\AppEnvironment;
use App\Models\AppPlatform;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppStartupAdminTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->admin = $this->authenticatedUser('admin');
    }

    private function seedAndroid(): AppPlatform
    {
        $platform = AppPlatform::updateOrCreate(
            ['platform_key' => 'android'],
            [
                'name' => ['en' => 'Android', 'ar' => 'أندرويد'],
                'latest_version' => '1.0.0',
                'minimum_required_version' => '1.0.0',
                'store_url' => 'https://play.google.com/store/test',
                'is_active' => 1,
                'order_column' => 1,
            ],
        );

        AppEnvironment::where('platform_id', $platform->id)->delete();
        AppEnvironment::create([
            'platform_id' => $platform->id,
            'name' => 'live',
            'base_url' => 'https://api.test/v1',
            'is_active' => 1,
        ]);

        return $platform;
    }

    public function test_unauthenticated_user_redirected_from_index(): void
    {
        $this->get('/admin/settings/app-startup')->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_index(): void
    {
        $this->seedAndroid();

        $this->withoutVite()
            ->actingAs($this->admin)
            ->get('/admin/settings/app-startup')
            ->assertOk();
    }

    public function test_admin_can_update_platform_version_policy(): void
    {
        $platform = $this->seedAndroid();

        $this->actingAs($this->admin)
            ->put("/admin/settings/app-startup/platforms/{$platform->id}", [
                'latest_version' => '2.0.0',
                'minimum_required_version' => '1.5.0',
                'store_url' => 'https://play.google.com/store/updated',
                'direct_apk_url' => null,
                'direct_apk_enabled' => false,
                'is_active' => true,
            ])
            ->assertRedirect();

        $platform->refresh();
        $this->assertSame('2.0.0', $platform->latest_version);
        $this->assertSame('1.5.0', $platform->minimum_required_version);
    }

    public function test_update_rejects_minimum_greater_than_latest(): void
    {
        $platform = $this->seedAndroid();

        $this->actingAs($this->admin)
            ->put("/admin/settings/app-startup/platforms/{$platform->id}", [
                'latest_version' => '1.0.0',
                'minimum_required_version' => '2.0.0',
                'store_url' => 'https://play.google.com/store/test',
            ])
            ->assertSessionHasErrors('minimum_required_version');
    }

    public function test_activating_environment_deactivates_siblings(): void
    {
        $platform = $this->seedAndroid();
        $stage = AppEnvironment::create([
            'platform_id' => $platform->id,
            'name' => 'stage',
            'base_url' => 'https://stage.test/v1',
            'is_active' => 0,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/settings/app-startup/environments/{$stage->id}/activate")
            ->assertRedirect();

        $this->assertTrue($stage->fresh()->is_active);
        $this->assertSame(1, AppEnvironment::where('platform_id', $platform->id)->where('is_active', 1)->count());
    }

    public function test_cannot_delete_active_environment(): void
    {
        $platform = $this->seedAndroid();
        $activeEnv = $platform->environments()->first();

        $this->actingAs($this->admin)
            ->delete("/admin/settings/app-startup/environments/{$activeEnv->id}")
            ->assertSessionHasErrors('environment');

        $this->assertNotNull($activeEnv->fresh());
    }

    public function test_admin_can_create_environment(): void
    {
        $platform = $this->seedAndroid();

        $this->actingAs($this->admin)
            ->post('/admin/settings/app-startup/environments', [
                'platform_id' => $platform->id,
                'name' => 'beta',
                'base_url' => 'https://beta.test/v1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_environments', [
            'platform_id' => $platform->id,
            'name' => 'beta',
            'base_url' => 'https://beta.test/v1',
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_toggle_maintenance(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings/app-startup/maintenance', [
                'is_active' => true,
                'message' => 'Brief downtime',
                'message_ar' => 'انقطاع قصير',
            ])
            ->assertRedirect();

        $window = MaintenanceWindow::latest('id')->first();
        $this->assertNotNull($window);
        $this->assertTrue($window->is_active);
        $this->assertSame('Brief downtime', $window->message);
    }
}
