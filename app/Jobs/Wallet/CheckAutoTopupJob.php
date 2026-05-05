<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sprint 3 SCAFFOLD ONLY — execution deferred to Sprint 8 (Hardening).
 *
 * The wallet/settings endpoints (B2/C1 of Sprint 3) persist auto_topup
 * configuration on `wallets.settings`. When fully wired, this job will
 * run on a schedule (per the spec: at least daily), iterate over wallets
 * with `auto_topup_enabled=true` and `available < auto_topup_threshold`,
 * and trigger a topup via the user's configured `auto_topup_payment_method`.
 *
 * Until Sprint 8 wires the schedule entry in routes/console.php (or
 * bootstrap/app.php->withSchedule), this job is dormant. The handle()
 * method is intentionally empty — running it is a no-op.
 *
 * Sprint 8 owners: enable handle() body + register schedule entry +
 * write a feature test.
 */
class CheckAutoTopupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Intentionally a no-op for Sprint 3. Sprint 8 owns:
        //
        // Wallet::query()
        //     ->whereJsonContains('settings->auto_topup_enabled', true)
        //     ->whereColumn('balance', '<', 'settings->auto_topup_threshold')
        //     ->each(fn(Wallet $w) => /* invoke topup gateway */ );
    }
}
