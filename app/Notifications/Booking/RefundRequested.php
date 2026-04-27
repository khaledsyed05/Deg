<?php

namespace App\Notifications\Booking;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RefundRequested extends Notification implements ShouldQueue
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
            'type' => 'refund_requested',
            'refund_request_id' => $this->request->id,
            'booking_id' => $this->request->booking_id,
            'status' => $this->request->status,
            'approved_amount' => $this->request->approved_amount,
            'auto_approved' => (bool) $this->request->auto_approved,
            'message_ar' => $this->request->auto_approved
                ? 'تمت الموافقة على طلب استرداد المبلغ'
                : 'تم استلام طلب الاسترداد، سيتم مراجعته قريباً',
        ];
    }
}
