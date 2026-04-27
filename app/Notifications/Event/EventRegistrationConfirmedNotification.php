<?php

namespace App\Notifications\Event;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventRegistrationConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventRegistration $registration) {}

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
        $event = $this->registration->event;

        return [
            'type' => 'event_registration_confirmed',
            'event_id' => $this->registration->event_id,
            'event_title_ar' => $event?->title_ar,
            'registration_number' => $this->registration->registration_number,
            'event_starts_at' => $event?->starts_at?->toIso8601String(),
            'venue_name' => $event?->venue?->getTranslation('name', 'ar', false),
            'message_ar' => 'تم تأكيد تسجيلك في '.($event?->title_ar ?? 'الفعالية'),
        ];
    }
}
