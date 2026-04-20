<?php

namespace Database\Seeders;

use App\Models\VenueCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VenueCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['ar' => 'ملاعب كرة قدم',  'en' => 'Football Pitches',    'type' => 'sports'],
            ['ar' => 'ملاعب كرة سلة',  'en' => 'Basketball Courts',   'type' => 'court'],
            ['ar' => 'ملاعب تنس',       'en' => 'Tennis Courts',       'type' => 'court'],
            ['ar' => 'صالات رياضية',    'en' => 'Gyms',                'type' => 'hall'],
            ['ar' => 'ملاعب كرة طائرة', 'en' => 'Volleyball Courts',  'type' => 'court'],
            ['ar' => 'ملاعب بادل',      'en' => 'Padel Courts',        'type' => 'court'],
            ['ar' => 'ملاعب سباحة',     'en' => 'Swimming Pools',      'type' => 'outdoor'],
        ];

        foreach ($categories as $i => $cat) {
            VenueCategory::firstOrCreate(
                ['slug' => Str::slug($cat['en'])],
                [
                    'name'         => ['ar' => $cat['ar'], 'en' => $cat['en']],
                    'slug'         => Str::slug($cat['en']),
                    'type'         => $cat['type'],
                    'is_active'    => true,
                    'order_column' => $i + 1,
                ]
            );
        }

        $this->command->info('✓ Venue categories seeded: ' . VenueCategory::count() . ' categories');
    }
}
