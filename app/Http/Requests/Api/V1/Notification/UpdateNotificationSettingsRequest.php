<?php

namespace App\Http\Requests\Api\V1\Notification;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $types = array_map(fn (NotificationType $t) => $t->value, NotificationType::cases());

        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['array'],
            'settings.*.enabled' => ['sometimes', 'boolean'],
            'settings.*.push_enabled' => ['sometimes', 'boolean'],
            'settings.*.sms_enabled' => ['sometimes', 'boolean'],
            'settings.*.email_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function typedSettings(): array
    {
        $allowed = array_map(fn (NotificationType $t) => $t->value, NotificationType::cases());
        $out = [];

        foreach ($this->input('settings', []) as $type => $values) {
            if (! in_array($type, $allowed, true) || ! is_array($values)) {
                continue;
            }
            $out[$type] = array_intersect_key($values, array_flip(['enabled', 'push_enabled', 'sms_enabled', 'email_enabled']));
        }

        return $out;
    }
}
