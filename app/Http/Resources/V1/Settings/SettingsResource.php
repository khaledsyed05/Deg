<?php

namespace App\Http\Resources\V1\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'language' => $this->getLanguage(),
            'timezone' => $this->timezone ?? 'Asia/Damascus',
            'privacy' => $this->getPrivacySettings(),
            'preferences' => $this->getPreferences(),
            'notifications' => $this->whenLoaded('notificationSettings', function () {
                return $this->notificationSettings
                    ->mapWithKeys(fn ($setting) => [
                        $setting->notification_type => [
                            'enabled' => (bool) $setting->enabled,
                            'push_enabled' => (bool) $setting->push_enabled,
                            'sms_enabled' => (bool) $setting->sms_enabled,
                            'email_enabled' => (bool) $setting->email_enabled,
                        ],
                    ]);
            }),
        ];
    }
}
