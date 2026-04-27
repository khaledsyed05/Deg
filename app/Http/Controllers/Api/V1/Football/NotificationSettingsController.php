<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Football\UpdateNotificationSettingsRequest;
use App\Http\Resources\Football\MatchNotificationSettingsResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $settings = $request->user()->getOrCreateMatchNotificationSettings();

        return $this->success(new MatchNotificationSettingsResource($settings));
    }

    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $settings = $request->user()->getOrCreateMatchNotificationSettings();
        $settings->fill($request->validated())->save();

        return $this->success(new MatchNotificationSettingsResource($settings->fresh()));
    }

    public function updateReminders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'match_reminders_enabled' => ['sometimes', 'boolean'],
            'reminder_minutes_before' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'second_reminder_enabled' => ['sometimes', 'boolean'],
            'second_reminder_minutes_before' => ['sometimes', 'integer', 'min:1', 'max:120'],
        ]);

        $settings = $request->user()->getOrCreateMatchNotificationSettings();
        $settings->fill($data)->save();

        return $this->success(new MatchNotificationSettingsResource($settings->fresh()));
    }

    public function updateGoals(Request $request): JsonResponse
    {
        $data = $request->validate([
            'goal_notifications_enabled' => ['required', 'boolean'],
        ]);
        $settings = $request->user()->getOrCreateMatchNotificationSettings();
        $settings->fill($data)->save();

        return $this->success(new MatchNotificationSettingsResource($settings->fresh()));
    }

    public function updateResults(Request $request): JsonResponse
    {
        $data = $request->validate([
            'result_notifications_enabled' => ['required', 'boolean'],
            'daily_summary_enabled' => ['sometimes', 'boolean'],
            'daily_summary_time' => ['sometimes', 'date_format:H:i'],
        ]);
        $settings = $request->user()->getOrCreateMatchNotificationSettings();
        $settings->fill($data)->save();

        return $this->success(new MatchNotificationSettingsResource($settings->fresh()));
    }

    public function updateQuietHours(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quiet_hours_enabled' => ['required', 'boolean'],
            'quiet_hours_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'date_format:H:i'],
        ]);
        $settings = $request->user()->getOrCreateMatchNotificationSettings();
        $settings->fill($data)->save();

        return $this->success(new MatchNotificationSettingsResource($settings->fresh()));
    }
}
