<?php

namespace App\Console\Commands\Geography;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('geography:update-counts')]
#[Description('Refresh cities.venues_count from venues joined via clubs')]
class UpdateCityVenueCounts extends Command
{
    public function handle(): int
    {
        DB::statement('
            UPDATE cities c
            SET venues_count = (
                SELECT COUNT(*) FROM venues v
                INNER JOIN clubs cl ON cl.id = v.club_id
                WHERE cl.city_id = c.id
                  AND v.deleted_at IS NULL
            )
        ');

        Cache::flush();
        $this->info('City venue counts updated');

        return self::SUCCESS;
    }
}
