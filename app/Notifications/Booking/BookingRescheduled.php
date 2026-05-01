<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingRescheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $history = $this->booking->reschedule_history ?? [];
        $latest = end($history) ?: [];

        return [
            'type' => 'booking_rescheduled',
            'booking_id' => $this->booking->id,
            'previous_schedule' => $latest['from'] ?? null,
            'new_schedule' => $latest['to'] ?? null,
            'price_difference' => $latest['price_difference'] ?? 0,
            'message_ar' => 'تم تغيير موعد حجزك',
        ];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $venue = $this->booking->venue?->getTranslation('name', 'ar') ?? '';

        return [
            'type' => 'booking_rescheduled',
            'data' => [
                'booking_id' => (string) $this->booking->id,
                'venue_name' => (string) $venue,
            ],
        ];
    }
}
