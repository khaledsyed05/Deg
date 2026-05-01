<?php

namespace App\Jobs\Notification;

use App\Models\Settlement;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SettlementPaidNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Settlement $settlement,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $owner = $this->settlement->club?->owner;

        if (! $owner) {
            return;
        }

        $push->sendNotification($owner, 'settlement_paid', [
            'amount' => (float) ($this->settlement->paid_amount ?? $this->settlement->net_payable),
            'settlement_id' => (string) $this->settlement->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SettlementPaidNotificationJob failed', [
            'settlement_id' => $this->settlement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
