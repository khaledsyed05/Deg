<?php

namespace Tests\Feature\Notification;

use App\Jobs\Notification\BookingConfirmedNotificationJob;
use App\Models\Booking;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Notification\PushNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Tests\TestCase;

class BookingConfirmedNotificationJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dispatches_through_push_service_with_booking_context(): void
    {
        $user = User::factory()->create(['preferred_language' => 'ar']);
        UserDevice::create(['user_id' => $user->id, 'device_id' => 'd1', 'fcm_token' => 'tok-1', 'platform' => 'ios']);
        NotificationSetting::createDefaultsForUser($user->id);

        $booking = Booking::factory()->confirmed()->create(['user_id' => $user->id]);

        $captured = null;

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendNotification')
            ->once()
            ->andReturnUsing(function ($recipient, $type, $data) use (&$captured) {
                $captured = ['recipient_id' => $recipient->id, 'type' => $type, 'data' => $data];

                return true;
            });

        $this->app->instance(PushNotificationService::class, $push);

        (new BookingConfirmedNotificationJob($booking))->handle($push);

        $this->assertSame($user->id, $captured['recipient_id']);
        $this->assertSame('booking_confirmed', $captured['type']);
        $this->assertSame((string) $booking->id, $captured['data']['booking_id']);
        $this->assertSame((string) $booking->booking_code, $captured['data']['booking_code']);
    }

    public function test_returns_silently_when_user_missing(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        $booking->setRelation('user', null);

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldNotReceive('sendNotification');

        (new BookingConfirmedNotificationJob($booking))->handle($push);

        $this->assertTrue(true);
    }
}
