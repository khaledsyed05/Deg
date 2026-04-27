<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletTopupSucceeded extends Notification implements ShouldQueue
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
            'type' => 'wallet_topup_success',
            'payment_id' => $this->payment->id,
            'amount' => (int) $this->payment->amount,
            'bonus' => (int) $this->payment->bonus_amount,
            'total_credited' => (int) ($this->payment->total_credited ?? ($this->payment->amount + $this->payment->bonus_amount)),
            'method' => $this->payment->provider?->value,
        ];
    }
}
