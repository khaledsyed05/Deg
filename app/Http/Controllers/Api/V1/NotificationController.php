<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\UpdateNotificationSettingsRequest;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Http\Traits\ApiResponse;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return $this->success([
            'notifications' => NotificationResource::collection($notifications),
            'unread_count' => $notifications->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return $this->success(null, 'تم تحديد الإشعار كمقروء');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(null, 'تم تحديد جميع الإشعارات كمقروءة');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return $this->success(null, 'تم حذف الإشعار');
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return $this->success(null, 'تم حذف جميع الإشعارات');
    }

    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        // Backfill defaults for new users.
        if (! $user->notificationSettings()->exists()) {
            NotificationSetting::createDefaultsForUser($user->id);
        }

        $settings = $user->notificationSettings()
            ->get()
            ->mapWithKeys(fn (NotificationSetting $s) => [
                $s->notification_type => [
                    'enabled' => (bool) $s->enabled,
                    'push_enabled' => (bool) $s->push_enabled,
                    'sms_enabled' => (bool) $s->sms_enabled,
                    'email_enabled' => (bool) $s->email_enabled,
                ],
            ]);

        return $this->success($settings);
    }

    public function updateSettings(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        foreach ($request->typedSettings() as $type => $values) {
            NotificationSetting::updateOrCreate(
                ['user_id' => $userId, 'notification_type' => $type],
                $values,
            );
        }

        return $this->success(null, 'تم تحديث إعدادات الإشعارات');
    }
}
