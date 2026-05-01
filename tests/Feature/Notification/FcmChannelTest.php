<?php

namespace Tests\Feature\Notification;

use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\AchievementUnlockedNotification;
use App\Notifications\Channels\FcmChannel;
use App\Services\Notification\FcmService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_achievement_notification_routes_to_database_and_fcm(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $achievement = $this->makeAchievement($user);

        $user->notify(new AchievementUnlockedNotification($achievement));

        Notification::assertSentTo($user, AchievementUnlockedNotification::class, function ($notification, $channels) {
            return in_array('database', $channels, true)
                && in_array(FcmChannel::class, $channels, true);
        });
    }

    public function test_fcm_channel_delegates_to_push_service(): void
    {
        $user = User::factory()->create(['preferred_language' => 'ar']);
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd1', 'fcm_token' => 'tok-1', 'platform' => 'ios']);
        NotificationSetting::createDefaultsForUser($user->id);

        $captured = [];

        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldReceive('sendToToken')->andReturnUsing(function ($token, $title) use (&$captured) {
            $captured = ['token' => $token, 'title' => $title];

            return true;
        });

        $this->app->instance(FcmService::class, $fcm);

        $achievement = $this->makeAchievement($user);

        $user->notify(new AchievementUnlockedNotification($achievement));

        $this->assertNotEmpty($captured, 'FCM channel should have called FcmService::sendToToken');
        $this->assertSame('tok-1', $captured['token']);
        $this->assertStringContainsString('🏆', $captured['title']);
    }

    private function makeAchievement(User $user): Achievement
    {
        return Achievement::create([
            'user_id' => $user->id,
            'type' => AchievementType::FirstBooking,
            'progress' => 1,
            'target' => 1,
        ]);
    }
}
