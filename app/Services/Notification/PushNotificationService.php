<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

/**
 * High-level push delivery. Wraps the low-level FcmService with per-user
 * fan-out, invalid-token cleanup, and per-type preference gating.
 */
class PushNotificationService
{
    public function __construct(
        private readonly FcmService $fcm,
    ) {}

    /**
     * Deliver a raw push to every FCM token on the user's devices (plus the
     * legacy users.fcm_token). Returns the number of tokens we sent to.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokens = $this->collectTokens($user);

        if (empty($tokens)) {
            Log::info('push.skip.no_tokens', ['user_id' => $user->id]);

            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            $ok = $this->fcm->sendToToken($token, $title, $body, $data);
            if ($ok) {
                $sent++;
            } else {
                UserDevice::where('fcm_token', $token)->update(['fcm_token' => null]);
            }
        }

        return $sent;
    }

    /**
     * Deliver a typed notification, respecting user preferences and locale.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendNotification(User $user, string $type, array $data = []): bool
    {
        if (! $user->wantsNotification($type, 'push')) {
            return false;
        }

        $content = $this->getNotificationContent($type, $data, $user->getLanguage());

        $sent = $this->sendToUser(
            $user,
            $content['title'],
            $content['body'],
            array_merge($data, ['type' => $type]),
        );

        return $sent > 0;
    }

    /**
     * @return array<int, string>
     */
    private function collectTokens(User $user): array
    {
        $tokens = $user->devices()
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->all();

        if ($user->fcm_token) {
            $tokens[] = $user->fcm_token;
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string}
     */
    private function getNotificationContent(string $type, array $data, string $language): array
    {
        $previous = app()->getLocale();
        app()->setLocale($language);

        try {
            $titleKey = "notifications.{$type}.title";
            $bodyKey = "notifications.{$type}.body";

            $replacements = [
                'venue' => (string) ($data['venue_name'] ?? ''),
                'date' => (string) ($data['booking_date'] ?? ''),
                'time' => (string) ($data['start_time'] ?? ''),
                'amount' => number_format((float) ($data['amount'] ?? 0)),
                'discount' => (string) ($data['discount'] ?? ''),
            ];

            $title = __($titleKey, $replacements);
            $body = __($bodyKey, $replacements);

            if ($title === $titleKey) {
                $title = (string) ($data['title'] ?? ($language === 'ar' ? 'إشعار جديد' : 'New notification'));
            }
            if ($body === $bodyKey) {
                $body = (string) ($data['message'] ?? '');
            }

            return ['title' => $title, 'body' => $body];
        } finally {
            app()->setLocale($previous);
        }
    }
}
