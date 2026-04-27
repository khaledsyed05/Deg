<?php

namespace App\Notifications\Booking;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RefundCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RefundRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund_completed',
            'refund_request_id' => $this->request->id,
            'booking_id' => $this->request->booking_id,
            'amount' => $this->request->approved_amount,
            'method' => $this->request->refund_method,
            'message_ar' => 'تم إيداع مبلغ الاسترداد في محفظتك',
        ];
    }
}
