<?php

namespace App\Jobs\Notification;

use App\Services\Notification\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $phoneNumber,
        public string $message,
    ) {}

    public function handle(SmsService $sms): void
    {
        $sms->send($this->phoneNumber, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob failed', [
            'phone' => $this->phoneNumber,
            'error' => $exception->getMessage(),
        ]);
    }
}
