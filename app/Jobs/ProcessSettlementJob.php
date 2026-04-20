<?php

namespace App\Jobs;

use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSettlementJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $maxExceptions = 2;

    public function __construct(public Settlement $settlement)
    {
        $this->onQueue('high');
    }

    public function handle(SettlementService $service): void
    {
        $service->processSettlement($this->settlement);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Settlement processing failed', [
            'settlement_id' => $this->settlement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
