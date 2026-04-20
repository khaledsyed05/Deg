<?php

namespace App\Jobs\Club;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateClubOnboardingProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public int $clubId,
    ) {}

    public function handle(): void
    {
        $club = Club::with(['venues'])->find($this->clubId);

        if (! $club) {
            return;
        }

        // Onboarding progress is determined by which data the club has filled.
        // Currently a read operation — future phases will persist a progress score.
    }
}
