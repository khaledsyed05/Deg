<?php

namespace Tests\Feature\Phase18\Devices;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UnregisterDeviceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_unregister_their_device(): void
    {
        $user = User::factory()->create();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => 'dev-1',
            'fcm_token' => 'tok-1',
            'platform' => 'ios',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/devices/{$device->id}")
            ->assertOk();

        $this->assertDatabaseMissing('user_devices', ['id' => $device->id]);
    }

    public function test_user_cannot_unregister_someone_elses_device(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $device = UserDevice::create([
            'user_id' => $other->id,
            'device_id' => 'dev-2',
            'fcm_token' => 'tok-2',
            'platform' => 'ios',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/devices/{$device->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('user_devices', ['id' => $device->id]);
    }
}
