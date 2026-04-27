<?php

namespace App\Models\Football;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMatchNotificationSettings extends Model
{
    protected $table = 'user_match_notification_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'match_reminders_enabled' => 'boolean',
            'second_reminder_enabled' => 'boolean',
            'goal_notifications_enabled' => 'boolean',
            'result_notifications_enabled' => 'boolean',
            'daily_summary_enabled' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
            'daily_summary_time' => 'datetime:H:i:s',
            'quiet_hours_start' => 'datetime:H:i:s',
            'quiet_hours_end' => 'datetime:H:i:s',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
