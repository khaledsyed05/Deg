<?php

namespace App\Notifications\Event;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventRegistrationCancelledNotification extends Notification implements ShouldQueue
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
        $refunded = $this->registration->status === 'refunded';

        return [
            'type' => 'event_registration_cancelled',
            'event_id' => $this->registration->event_id,
            'event_title_ar' => $event?->title_ar,
            'registration_number' => $this->registration->registration_number,
            'refunded' => $refunded,
            'refunded_amount' => $refunded ? (float) $this->registration->amount_paid : 0,
            'message_ar' => 'تم إلغاء تسجيلك في '.($event?->title_ar ?? 'الفعالية'),
        ];
    }
}
