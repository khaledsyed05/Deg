<?php

namespace App\Notifications\Event;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event, public string $window) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->window === '24h' ? 'خلال 24 ساعة' : 'خلال ساعة';

        return [
            'type' => 'event_reminder',
            'event_id' => $this->event->id,
            'event_title_ar' => $this->event->title_ar,
            'starts_at' => $this->event->starts_at?->toIso8601String(),
            'window' => $this->window,
            'message_ar' => "تذكير: تبدأ {$this->event->title_ar} {$label}",
        ];
    }
}
