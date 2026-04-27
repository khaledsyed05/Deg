<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\Venue;
use Illuminate\Console\Command;

class GenerateSlugs extends Command
{
    protected $signature = 'slugs:generate';

    protected $description = 'Generate slugs for existing records that do not have one';

    public function handle(): void
    {
        $this->info('Generating slugs for Venues...');
        Venue::withTrashed()->whereNull('slug')->each(function (Venue $venue) {
            $venue->generateSlug();
            $venue->saveQuietly();
            $this->line("  ✓ Venue {$venue->id}: {$venue->slug}");
        });

        $this->info('Generating slugs for Clubs...');
        Club::withTrashed()->whereNull('slug')->each(function (Club $club) {
            $club->generateSlug();
            $club->saveQuietly();
            $this->line("  ✓ Club {$club->id}: {$club->slug}");
        });

        $this->info('Done.');
    }
}
