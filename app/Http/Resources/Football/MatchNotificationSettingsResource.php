<?php

namespace App\Http\Resources\Football;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchNotificationSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'match_reminders_enabled' => (bool) $this->match_reminders_enabled,
            'reminder_minutes_before' => (int) $this->reminder_minutes_before,
            'second_reminder_enabled' => (bool) $this->second_reminder_enabled,
            'second_reminder_minutes_before' => (int) $this->second_reminder_minutes_before,
            'goal_notifications_enabled' => (bool) $this->goal_notifications_enabled,
            'result_notifications_enabled' => (bool) $this->result_notifications_enabled,
            'daily_summary_enabled' => (bool) $this->daily_summary_enabled,
            'daily_summary_time' => $this->daily_summary_time?->format('H:i'),
            'quiet_hours_enabled' => (bool) $this->quiet_hours_enabled,
            'quiet_hours_start' => $this->quiet_hours_start?->format('H:i'),
            'quiet_hours_end' => $this->quiet_hours_end?->format('H:i'),
        ];
    }
}
