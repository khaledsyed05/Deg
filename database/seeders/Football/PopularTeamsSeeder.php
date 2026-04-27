<?php

namespace Database\Seeders\Football;

use App\Models\Football\League;
use App\Models\Football\Team;
use Illuminate\Database\Seeder;

class PopularTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['external_id' => '86', 'name' => 'Real Madrid', 'name_ar' => 'ريال مدريد', 'tla' => 'REA', 'league_code' => 'PD', 'rank' => 1, 'api_sports_id' => 541],
            ['external_id' => '81', 'name' => 'FC Barcelona', 'name_ar' => 'برشلونة', 'tla' => 'BAR', 'league_code' => 'PD', 'rank' => 2, 'api_sports_id' => 529],
            ['external_id' => '64', 'name' => 'Liverpool FC', 'name_ar' => 'ليفربول', 'tla' => 'LIV', 'league_code' => 'PL', 'rank' => 3, 'api_sports_id' => 40],
            ['external_id' => '65', 'name' => 'Manchester City', 'name_ar' => 'مانشستر سيتي', 'tla' => 'MCI', 'league_code' => 'PL', 'rank' => 4, 'api_sports_id' => 50],
            ['external_id' => '66', 'name' => 'Manchester United', 'name_ar' => 'مانشستر يونايتد', 'tla' => 'MUN', 'league_code' => 'PL', 'rank' => 5, 'api_sports_id' => 33],
            ['external_id' => '5', 'name' => 'Bayern München', 'name_ar' => 'بايرن ميونخ', 'tla' => 'FCB', 'league_code' => 'BL1', 'rank' => 6, 'api_sports_id' => 157],
            ['external_id' => '109', 'name' => 'Juventus', 'name_ar' => 'يوفنتوس', 'tla' => 'JUV', 'league_code' => 'SA', 'rank' => 7, 'api_sports_id' => 496],
            ['external_id' => '524', 'name' => 'Paris Saint-Germain', 'name_ar' => 'باريس سان جيرمان', 'tla' => 'PSG', 'league_code' => 'FL1', 'rank' => 8, 'api_sports_id' => 85],
            ['external_id' => '57', 'name' => 'Arsenal FC', 'name_ar' => 'أرسنال', 'tla' => 'ARS', 'league_code' => 'PL', 'rank' => 9, 'api_sports_id' => 42],
            ['external_id' => '61', 'name' => 'Chelsea FC', 'name_ar' => 'تشيلسي', 'tla' => 'CHE', 'league_code' => 'PL', 'rank' => 10, 'api_sports_id' => 49],
            ['external_id' => '73', 'name' => 'Tottenham Hotspur', 'name_ar' => 'توتنهام', 'tla' => 'TOT', 'league_code' => 'PL', 'rank' => 11, 'api_sports_id' => 47],
            ['external_id' => '78', 'name' => 'Atlético Madrid', 'name_ar' => 'أتلتيكو مدريد', 'tla' => 'ATM', 'league_code' => 'PD', 'rank' => 12, 'api_sports_id' => 530],
            ['external_id' => '4', 'name' => 'Borussia Dortmund', 'name_ar' => 'بوروسيا دورتموند', 'tla' => 'BVB', 'league_code' => 'BL1', 'rank' => 13, 'api_sports_id' => 165],
            ['external_id' => '108', 'name' => 'Inter Milan', 'name_ar' => 'إنتر ميلان', 'tla' => 'INT', 'league_code' => 'SA', 'rank' => 14, 'api_sports_id' => 505],
            ['external_id' => '98', 'name' => 'AC Milan', 'name_ar' => 'ميلان', 'tla' => 'MIL', 'league_code' => 'SA', 'rank' => 15, 'api_sports_id' => 489],
            ['external_id' => '113', 'name' => 'SSC Napoli', 'name_ar' => 'نابولي', 'tla' => 'NAP', 'league_code' => 'SA', 'rank' => 16, 'api_sports_id' => 492],
            ['external_id' => '100', 'name' => 'AS Roma', 'name_ar' => 'روما', 'tla' => 'ROM', 'league_code' => 'SA', 'rank' => 17, 'api_sports_id' => 497],
            ['external_id' => '518', 'name' => 'Olympique Marseille', 'name_ar' => 'مارسيليا', 'tla' => 'MAR', 'league_code' => 'FL1', 'rank' => 18, 'api_sports_id' => 81],
            ['external_id' => '559', 'name' => 'Sevilla FC', 'name_ar' => 'إشبيلية', 'tla' => 'SEV', 'league_code' => 'PD', 'rank' => 19, 'api_sports_id' => 536],
            ['external_id' => '6', 'name' => 'Schalke 04', 'name_ar' => 'شالكه', 'tla' => 'S04', 'league_code' => 'BL1', 'rank' => 20, 'api_sports_id' => 159],
        ];

        $duplicates = array_filter(
            array_count_values(array_column($teams, 'external_id')),
            fn ($count) => $count > 1
        );
        if (! empty($duplicates)) {
            throw new \RuntimeException('Duplicate external_ids in PopularTeamsSeeder: '.implode(',', array_keys($duplicates)));
        }

        foreach ($teams as $data) {
            $league = League::where('code', $data['league_code'])->first();
            Team::updateOrCreate(
                ['external_id' => $data['external_id']],
                [
                    'name' => $data['name'],
                    'name_ar' => $data['name_ar'],
                    'tla' => $data['tla'],
                    'league_id' => $league?->id,
                    'is_popular' => true,
                    'popularity_rank' => $data['rank'],
                    'api_sports_id' => $data['api_sports_id'] ?? null,
                ]
            );
        }
    }
}
