<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            GeographySeeder::class,
            RolesAndPermissionsSeeder::class,
            CommissionConfigSeeder::class,
            VenueCategorySeeder::class,
            SportCategorySeeder::class,
            ClubSeeder::class,
            VenueSeeder::class,
            UserSeeder::class,
            BookingSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
