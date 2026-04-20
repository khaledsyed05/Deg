<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تذكير بموعد حجزك — دق احجزلي')
            ->line("مرحباً {$notifiable->name}،")
            ->line("لديك حجز قادم في {$this->booking->venue->name['ar']} بتاريخ {$this->booking->booking_date} الساعة {$this->booking->start_time}.")
            ->action('عرض الحجز', url('/'))
            ->line('شكراً لاستخدامك دق احجزلي!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'starts_at' => $this->booking->starts_at?->toISOString(),
        ];
    }
}
