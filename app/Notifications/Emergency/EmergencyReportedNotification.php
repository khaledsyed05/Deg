<?php

namespace App\Notifications\Emergency;

use App\Models\Emergency\EmergencyReport;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class EmergencyReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmergencyReport $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'emergency_reported',
            'report_id' => $this->report->id,
            'severity' => $this->report->severity,
            'emergency_type' => $this->report->type,
            'reporter_user_id' => $this->report->user_id,
            'venue_id' => $this->report->venue_id,
            'description_preview' => Str::limit($this->report->description, 100),
            'location' => $this->report->latitude ? [
                'latitude' => (float) $this->report->latitude,
                'longitude' => (float) $this->report->longitude,
            ] : null,
            'message_ar' => '🚨 بلاغ طوارئ '.$this->getSeverityLabel($this->report->severity),
        ];
    }

    private function getSeverityLabel(string $severity): string
    {
        return match ($severity) {
            'critical' => 'حرج',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
            default => $severity,
        };
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'type' => 'emergency_reported',
            'data' => [
                'report_id' => (string) $this->report->id,
                'severity' => $this->getSeverityLabel($this->report->severity),
                'emergency_type' => (string) $this->report->type,
                'venue_id' => (string) ($this->report->venue_id ?? ''),
            ],
        ];
    }
}
