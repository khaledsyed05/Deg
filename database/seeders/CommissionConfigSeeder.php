<?php

namespace Database\Seeders;

use App\Enums\CommissionScope;
use App\Enums\CommissionType;
use App\Models\CommissionConfig;
use Illuminate\Database\Seeder;

class CommissionConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Global: 7% (700 basis points) commission on all bookings
        CommissionConfig::firstOrCreate(
            ['scope' => CommissionScope::Global, 'club_id' => null, 'venue_id' => null],
            [
                'scope'            => CommissionScope::Global,
                'club_id'          => null,
                'venue_id'         => null,
                'commission_type'  => CommissionType::Percentage,
                'commission_value' => 700, // 700 basis points = 7.00%
                'cancellation_fee' => 0,
                'is_active'        => true,
                'effective_from'   => now()->toDateString(),
                'note'             => 'Default global commission — 7%',
            ]
        );

        $this->command->info('✓ Commission config seeded: global 7% (700 bps)');
    }
}
