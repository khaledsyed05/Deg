<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Venue;
use App\Models\VenueCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VenueTopUpSeeder extends Seeder
{
    /**
     * Seed 50 realistic Syrian venues across existing Syrian clubs.
     * Idempotent: keyed on the generated slug.
     */
    public function run(): void
    {
        $syrianClubs = Club::whereIn('slug', [
            'al-jaish-club', 'al-wathba-club', 'al-ittihad-club',
            'tishreen-club', 'al-karamah-club', 'jableh-club', 'al-futowa-club',
        ])->get()->keyBy('slug');

        if ($syrianClubs->isEmpty()) {
            $this->command->warn('No Syrian clubs found — skipping VenueTopUpSeeder.');

            return;
        }

        $categories = VenueCategory::all()->keyBy('slug');

        $namePrefixAr = [
            'football-pitches' => ['ملعب', 'ملعب رقم', 'الملعب'],
            'basketball-courts' => ['قاعة السلة', 'صالة', 'الملعب'],
            'tennis-courts' => ['ملعب التنس', 'صالة التنس'],
            'gyms' => ['صالة', 'نادي', 'مركز'],
            'volleyball-courts' => ['ملعب الطائرة', 'صالة'],
            'padel-courts' => ['ملعب البادل', 'صالة البادل'],
            'swimming-pools' => ['مسبح', 'حوض السباحة'],
        ];

        $suffixAr = ['الرئيسي', 'الفرعي', 'الشمالي', 'الجنوبي', 'الداخلي', 'الخارجي', 'الأولمبي', 'المركزي', 'الذهبي', 'الملكي'];
        $suffixEn = ['Main', 'North', 'South', 'Indoor', 'Outdoor', 'Olympic', 'Central', 'Gold', 'Royal', 'Premier'];

        $amenitiesPool = [
            'إضاءة ليلية', 'غرف تبديل', 'دش ساخن', 'مواقف سيارات', 'كافيتيريا',
            'WiFi مجاني', 'تكييف', 'خدمة تأجير المعدات', 'مراقبة بالكاميرات', 'إسعافات أولية',
        ];

        $openingHours = collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->mapWithKeys(fn (string $day) => [
                $day => [
                    'open' => $day === 'friday' ? '14:00' : '08:00',
                    'close' => '23:00',
                    'closed' => false,
                ],
            ])->all();

        $created = 0;
        $clubSlugs = $syrianClubs->keys()->values();
        $categorySlugs = array_keys($namePrefixAr);

        for ($i = 1; $i <= 50; $i++) {
            $club = $syrianClubs[$clubSlugs[$i % $clubSlugs->count()]];
            $categorySlug = $categorySlugs[array_rand($categorySlugs)];
            $category = $categories[$categorySlug] ?? null;

            if (! $category) {
                continue;
            }

            $prefixAr = $namePrefixAr[$categorySlug][array_rand($namePrefixAr[$categorySlug])];
            $suffixIdx = array_rand($suffixAr);

            $nameAr = "{$prefixAr} {$suffixAr[$suffixIdx]} - {$club->getTranslation('name', 'ar')}";
            $nameEn = "{$club->getTranslation('name', 'en')} {$suffixEn[$suffixIdx]} #{$i}";

            $latJitter = (mt_rand(-100, 100) / 10000);
            $lngJitter = (mt_rand(-100, 100) / 10000);

            shuffle($amenitiesPool);
            $amenities = array_slice($amenitiesPool, 0, random_int(3, 6));

            $venue = Venue::firstOrCreate(
                ['slug' => Str::slug($nameEn)],
                [
                    'club_id' => $club->id,
                    'category_id' => $category->id,
                    'name' => ['ar' => $nameAr, 'en' => $nameEn],
                    'description' => [
                        'ar' => 'منشأة رياضية حديثة مجهزة بالكامل في سوريا.',
                        'en' => 'Modern fully-equipped sports facility in Syria.',
                    ],
                    'size' => ['30x50 م', '25x45 م', '15x28 م'][array_rand([0, 1, 2])],
                    'capacity' => random_int(10, 50),
                    'amenities' => $amenities,
                    'opening_hours' => $openingHours,
                    'latitude' => (float) $club->latitude + $latJitter,
                    'longitude' => (float) $club->longitude + $lngJitter,
                    'price_from' => random_int(30, 200) * 1000,
                    'status' => 'active',
                    'is_featured' => random_int(1, 100) <= 20,
                    'view_count' => random_int(50, 2500),
                ],
            );

            if ($venue->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("VenueTopUpSeeder: created {$created} new venues.");
    }
}
