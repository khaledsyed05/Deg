<?php

namespace App\Jobs\Notification;

use App\Models\Payment;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundCompletedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Payment $payment,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $user = $this->payment->user ?? $this->payment->booking?->user;

        if (! $user) {
            return;
        }

        $push->sendNotification($user, 'refund_completed', [
            'amount' => (float) ($this->payment->refund_amount ?? $this->payment->amount),
            'payment_id' => (string) $this->payment->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RefundCompletedNotificationJob failed', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
