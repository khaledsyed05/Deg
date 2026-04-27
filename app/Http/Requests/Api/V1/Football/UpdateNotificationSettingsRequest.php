<?php

namespace App\Http\Requests\Api\V1\Football;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_reminders_enabled' => ['sometimes', 'boolean'],
            'reminder_minutes_before' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'second_reminder_enabled' => ['sometimes', 'boolean'],
            'second_reminder_minutes_before' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'goal_notifications_enabled' => ['sometimes', 'boolean'],
            'result_notifications_enabled' => ['sometimes', 'boolean'],
            'daily_summary_enabled' => ['sometimes', 'boolean'],
            'daily_summary_time' => ['sometimes', 'date_format:H:i'],
            'quiet_hours_enabled' => ['sometimes', 'boolean'],
            'quiet_hours_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'date_format:H:i'],
        ];
    }
}
