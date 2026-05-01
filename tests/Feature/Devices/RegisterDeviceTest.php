<?php

namespace Tests\Feature\Devices;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterDeviceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_register_a_device(): void
    {
        $user = User::factory()->create();

        $payload = [
            'device_id' => 'iphone-uuid-123',
            'fcm_token' => 'fcm-token-abc',
            'platform' => 'ios',
            'device_name' => 'iPhone 15',
            'app_version' => '1.0.0',
            'os_version' => '17.4',
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices', $payload)
            ->assertOk()
            ->assertJsonPath('data.device_id', 'iphone-uuid-123')
            ->assertJsonPath('data.platform', 'ios');

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'iphone-uuid-123',
            'fcm_token' => 'fcm-token-abc',
            'platform' => 'ios',
        ]);
    }

    public function test_re_registering_same_device_id_updates_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices', [
                'device_id' => 'd-1',
                'fcm_token' => 'old-tok',
                'platform' => 'android',
            ])->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices', [
                'device_id' => 'd-1',
                'fcm_token' => 'new-tok',
                'platform' => 'android',
            ])->assertOk();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'd-1',
            'fcm_token' => 'new-tok',
        ]);
        $this->assertDatabaseMissing('user_devices', ['fcm_token' => 'old-tok']);
    }

    public function test_invalid_platform_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices', [
                'device_id' => 'd-1',
                'fcm_token' => 'tok',
                'platform' => 'windows',
            ])->assertStatus(422);
    }
}
