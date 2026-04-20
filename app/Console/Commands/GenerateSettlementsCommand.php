<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Option;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('settlements:generate {--club-id= : Limit to a specific club ID}')]
#[Description('Generate monthly settlements for clubs')]
class GenerateSettlementsCommand extends Command
{
    public function handle(SettlementService $service): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $clubId = $this->option('club-id');

        $clubs = $clubId
            ? Club::where('id', $clubId)->get()
            : Club::where('status', 'active')->get();

        foreach ($clubs as $club) {
            $this->info("Generating settlement for: {$club->name['ar']}");

            $settlement = $service->draft($club, $startOfMonth, $endOfMonth);

            $this->info("Settlement #{$settlement->settlement_code} created");
        }

        return self::SUCCESS;
    }
}
