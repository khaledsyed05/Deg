<?php

namespace Database\Seeders\Football;

use App\Models\Football\League;
use Illuminate\Database\Seeder;

class FeaturedLeaguesSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            ['code' => 'PL', 'name' => 'Premier League', 'name_ar' => 'الدوري الإنجليزي الممتاز', 'country' => 'England', 'country_ar' => 'إنجلترا', 'api_sports_id' => 39],
            ['code' => 'PD', 'name' => 'La Liga', 'name_ar' => 'الدوري الإسباني', 'country' => 'Spain', 'country_ar' => 'إسبانيا', 'api_sports_id' => 140],
            ['code' => 'CL', 'name' => 'UEFA Champions League', 'name_ar' => 'دوري أبطال أوروبا', 'country' => 'Europe', 'country_ar' => 'أوروبا', 'api_sports_id' => 2],
            ['code' => 'BL1', 'name' => 'Bundesliga', 'name_ar' => 'الدوري الألماني', 'country' => 'Germany', 'country_ar' => 'ألمانيا', 'api_sports_id' => 78],
            ['code' => 'SA', 'name' => 'Serie A', 'name_ar' => 'الدوري الإيطالي', 'country' => 'Italy', 'country_ar' => 'إيطاليا', 'api_sports_id' => 135],
            ['code' => 'FL1', 'name' => 'Ligue 1', 'name_ar' => 'الدوري الفرنسي', 'country' => 'France', 'country_ar' => 'فرنسا', 'api_sports_id' => 61],
            ['code' => 'DED', 'name' => 'Eredivisie', 'name_ar' => 'الدوري الهولندي', 'country' => 'Netherlands', 'country_ar' => 'هولندا', 'api_sports_id' => 88],
            ['code' => 'PPL', 'name' => 'Primeira Liga', 'name_ar' => 'الدوري البرتغالي', 'country' => 'Portugal', 'country_ar' => 'البرتغال', 'api_sports_id' => 94],
            ['code' => 'BSA', 'name' => 'Brasileirão Série A', 'name_ar' => 'الدوري البرازيلي', 'country' => 'Brazil', 'country_ar' => 'البرازيل', 'api_sports_id' => 71],
            ['code' => 'ELC', 'name' => 'EFL Championship', 'name_ar' => 'دوري البطولة الإنجليزي', 'country' => 'England', 'country_ar' => 'إنجلترا', 'api_sports_id' => 40],
            ['code' => 'EC', 'name' => 'European Championship', 'name_ar' => 'كأس أمم أوروبا', 'country' => 'Europe', 'country_ar' => 'أوروبا', 'api_sports_id' => 4],
            ['code' => 'WC', 'name' => 'FIFA World Cup', 'name_ar' => 'كأس العالم', 'country' => 'World', 'country_ar' => 'العالم', 'api_sports_id' => 1],
        ];

        foreach ($leagues as $i => $data) {
            League::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'external_id' => 'placeholder_'.$data['code'],
                    'is_active' => true,
                    'is_featured' => true,
                    'display_order' => $i + 1,
                    'type' => 'LEAGUE',
                ])
            );
        }
    }
}
