<?php

namespace Tests\Feature\Notification;

use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Notification\FcmService;
use App\Services\Notification\PushNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_fans_out_to_all_devices_and_dedupes_legacy_token(): void
    {
        $user = User::factory()->create([
            'fcm_token' => 'tok-legacy',
            'preferred_language' => 'ar',
        ]);

        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd1', 'fcm_token' => 'tok-1', 'platform' => 'ios']);
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd2', 'fcm_token' => 'tok-2', 'platform' => 'android']);
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd3', 'fcm_token' => 'tok-legacy', 'platform' => 'ios']);

        $sentTokens = [];

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('sendToToken')
            ->andReturnUsing(function ($token) use (&$sentTokens) {
                $sentTokens[] = $token;

                return true;
            });

        $service = new PushNotificationService($fcm);

        $count = $service->sendToUser($user, 'Hello', 'World', ['x' => 'y']);

        $this->assertSame(3, $count);
        $this->assertEqualsCanonicalizing(['tok-1', 'tok-2', 'tok-legacy'], $sentTokens);
    }

    public function test_invalid_token_is_nulled_out(): void
    {
        $user = User::factory()->create();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => 'd1',
            'fcm_token' => 'bad-tok',
            'platform' => 'ios',
        ]);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('sendToToken')->andReturn(false);

        $service = new PushNotificationService($fcm);

        $service->sendToUser($user, 't', 'b');

        $this->assertNull($device->fresh()->fcm_token);
    }

    public function test_send_notification_respects_preferences(): void
    {
        $user = User::factory()->create();
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd1', 'fcm_token' => 'tok-1', 'platform' => 'ios']);

        NotificationSetting::createDefaultsForUser($user->id);
        NotificationSetting::where('user_id', $user->id)
            ->where('notification_type', 'booking_confirmed')
            ->update(['enabled' => false, 'push_enabled' => false]);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldNotReceive('sendToToken');

        $service = new PushNotificationService($fcm);

        $sent = $service->sendNotification($user, 'booking_confirmed', ['venue_name' => 'X']);

        $this->assertFalse($sent);
    }

    public function test_send_notification_localizes_via_user_language(): void
    {
        $user = User::factory()->create(['preferred_language' => 'en']);
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd1', 'fcm_token' => 'tok-1', 'platform' => 'ios']);

        NotificationSetting::createDefaultsForUser($user->id);

        $captured = [];

        /** @var MockInterface $fcm */
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('sendToToken')
            ->andReturnUsing(function ($token, $title, $body) use (&$captured) {
                $captured = ['title' => $title, 'body' => $body];

                return true;
            });

        $service = new PushNotificationService($fcm);

        $service->sendNotification($user, 'booking_confirmed', [
            'venue_name' => 'Camp Nou',
            'booking_date' => '2026-05-01',
        ]);

        $this->assertSame('Booking confirmed', $captured['title']);
        $this->assertStringContainsString('Camp Nou', $captured['body']);
        $this->assertStringContainsString('2026-05-01', $captured['body']);
    }

    public function test_send_notification_returns_zero_when_no_tokens(): void
    {
        $user = User::factory()->create();
        NotificationSetting::createDefaultsForUser($user->id);

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldNotReceive('sendToToken');

        $service = new PushNotificationService($fcm);

        $this->assertFalse($service->sendNotification($user, 'booking_confirmed', []));
    }
}
