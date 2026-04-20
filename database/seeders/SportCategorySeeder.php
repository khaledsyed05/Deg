<?php

namespace Database\Seeders;

use App\Models\SportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['ar' => 'كرة القدم',     'en' => 'Football'],
            ['ar' => 'كرة السلة',     'en' => 'Basketball'],
            ['ar' => 'التنس',          'en' => 'Tennis'],
            ['ar' => 'كرة الطائرة',   'en' => 'Volleyball'],
            ['ar' => 'اللياقة البدنية', 'en' => 'Fitness'],
            ['ar' => 'البادل',         'en' => 'Padel'],
            ['ar' => 'السباحة',        'en' => 'Swimming'],
        ];

        foreach ($sports as $i => $sport) {
            SportCategory::firstOrCreate(
                ['slug' => Str::slug($sport['en'])],
                [
                    'name'         => ['ar' => $sport['ar'], 'en' => $sport['en']],
                    'slug'         => Str::slug($sport['en']),
                    'is_active'    => true,
                    'order_column' => $i + 1,
                ]
            );
        }

        $this->command->info('✓ Sport categories seeded: ' . SportCategory::count() . ' sports');
    }
}
