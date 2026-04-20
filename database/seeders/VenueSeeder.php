<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Venue;
use App\Models\VenueCategory;
use App\Models\VenuePricingTier;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $footballCategory = VenueCategory::where('slug', 'football-pitches')->firstOrFail()->id;
        $basketballCategory = VenueCategory::where('slug', 'basketball-courts')->firstOrFail()->id;

        $jaishClub    = Club::where('slug', 'al-jaish-club')->firstOrFail()->id;
        $wathbaClub   = Club::where('slug', 'al-wathba-club')->firstOrFail()->id;
        $ittihadClub  = Club::where('slug', 'al-ittihad-club')->firstOrFail()->id;
        $tishreenClub = Club::where('slug', 'tishreen-club')->firstOrFail()->id;
        $karamahClub  = Club::where('slug', 'al-karamah-club')->firstOrFail()->id;

        $allWeekHours = collect([
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
        ])->mapWithKeys(fn ($day) => [
            $day => ['open' => '08:00', 'close' => '22:00', 'closed' => false],
        ])->all();

        $venues = [
            [
                'club_id'       => $jaishClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب الجلاء', 'en' => 'Al-Jaish Stadium'],
                'description'   => [
                    'ar' => 'ملعب كرة قدم بمواصفات دولية في دمشق، مجهز بإنارة ليلية وأرضية عشبية صناعية',
                    'en' => 'International standard football pitch in Damascus with night lighting and artificial turf',
                ],
                'latitude'      => 33.51380000,
                'longitude'     => 36.27650000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 50000,
                'status'        => 'active',
                'price'         => 50000,
            ],
            [
                'club_id'       => $jaishClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب الفيحاء', 'en' => 'Al-Fayhaa Stadium'],
                'description'   => [
                    'ar' => 'ملعب كرة قدم بأبعاد قياسية مع خدمات كاملة',
                    'en' => 'Standard football pitch with full facilities',
                ],
                'latitude'      => 33.51500000,
                'longitude'     => 36.28000000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 45000,
                'status'        => 'active',
                'price'         => 45000,
            ],
            [
                'club_id'       => $wathbaClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب خالد بن الوليد', 'en' => 'Khalid ibn al-Walid Stadium'],
                'description'   => [
                    'ar' => 'ملعب حمص الرئيسي، يسع 30,000 متفرج مع تسهيلات ممتازة',
                    'en' => 'Main Homs stadium with capacity of 30,000 and excellent facilities',
                ],
                'latitude'      => 34.73500000,
                'longitude'     => 36.72200000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 40000,
                'status'        => 'active',
                'price'         => 40000,
            ],
            [
                'club_id'       => $ittihadClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب حلب الدولي', 'en' => 'Aleppo International Stadium'],
                'description'   => [
                    'ar' => 'ملعب حلب الدولي بطاقة استيعابية 73,000 متفرج',
                    'en' => 'Aleppo International Stadium with 73,000 capacity',
                ],
                'latitude'      => 36.19000000,
                'longitude'     => 37.14000000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 45000,
                'status'        => 'active',
                'price'         => 45000,
            ],
            [
                'club_id'       => $tishreenClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب الباسل', 'en' => 'Al-Basel Stadium'],
                'description'   => [
                    'ar' => 'ملعب تشرين الرئيسي باللاذقية، أرضية طبيعية وإنارة ليلية',
                    'en' => 'Tishreen main stadium in Latakia with natural grass and night lighting',
                ],
                'latitude'      => 35.52000000,
                'longitude'     => 35.78000000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 40000,
                'status'        => 'active',
                'price'         => 40000,
            ],
            [
                'club_id'       => $karamahClub,
                'category_id'   => $footballCategory,
                'name'          => ['ar' => 'ملعب الكرامة', 'en' => 'Al-Karamah Stadium'],
                'description'   => [
                    'ar' => 'ملعب الكرامة في حمص، يحتاج صيانة (غير مفعّل مؤقتاً)',
                    'en' => 'Al-Karamah stadium in Homs, under maintenance (temporarily inactive)',
                ],
                'latitude'      => 34.74000000,
                'longitude'     => 36.71500000,
                'opening_hours' => $allWeekHours,
                'price_from'    => 40000,
                'status'        => 'inactive',
                'price'         => 40000,
            ],
        ];

        foreach ($venues as $data) {
            $price = $data['price'];
            unset($data['price'], $data['is_standard']);

            $nameEn = $data['name']['en'];
            $existing = Venue::where('club_id', $data['club_id'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) = ?", [$nameEn])
                ->first();

            $venue = $existing ?? Venue::create($data);

            // Create a default pricing tier (60-min slots, all days)
            if ($venue->wasRecentlyCreated) {
                VenuePricingTier::create([
                    'venue_id'         => $venue->id,
                    'name'             => ['ar' => 'السعر الأساسي', 'en' => 'Standard Rate'],
                    'day_type'         => 'all_days',
                    'start_time'       => '08:00',
                    'end_time'         => '22:00',
                    'duration_minutes' => 60,
                    'price'            => $price,
                    'is_active'        => true,
                    'order_column'     => 1,
                ]);
            }
        }

        $this->command->info('✓ Venues seeded: ' . Venue::count() . ' venues with pricing tiers');
    }
}
