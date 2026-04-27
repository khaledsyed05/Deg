<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletTopupFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wallet_topup_failed',
            'payment_id' => $this->payment->id,
            'amount' => (int) $this->payment->amount,
            'method' => $this->payment->provider?->value,
            'reason' => $this->payment->failure_reason,
        ];
    }
}
