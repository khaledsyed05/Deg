<?php

namespace App\Console\Commands;

use App\Services\Wallet\WalletService;
use Illuminate\Console\Command;

class ExpireWalletCredits extends Command
{
    protected $signature = 'wallet:expire-credits';

    protected $description = 'Expire promotional wallet credits that have passed their expires_at date';

    public function handle(WalletService $service): int
    {
        $this->info('Expiring wallet credits...');

        $totalExpired = $service->expireCredits();

        $this->info("Expired {$totalExpired} SYP in credits");

        return self::SUCCESS;
    }
}
