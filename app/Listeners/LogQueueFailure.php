<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogQueueFailure
{
    public function handle(JobFailed $event): void
    {
        Log::error('Queue job failed', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job' => $event->job->resolveName(),
            'exception' => $event->exception->getMessage(),
            'payload' => $event->job->payload(),
        ]);
    }
}
