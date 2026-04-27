<?php

namespace Database\Seeders\Football;

use Illuminate\Database\Seeder;

class FootballSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeaturedLeaguesSeeder::class,
            PopularTeamsSeeder::class,
        ]);
    }
}
