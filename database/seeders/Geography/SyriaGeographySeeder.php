<?php

namespace Database\Seeders\Geography;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class SyriaGeographySeeder extends Seeder
{
    public function run(): void
    {
        $syria = Country::updateOrCreate(
            ['iso2' => 'SY'],
            [
                'iso3' => 'SYR',
                'name' => 'Syria',
                'name_ar' => 'سوريا',
                'phone_code' => '+963',
                'currency' => 'SYP',
                'currency_symbol' => 'ل.س',
                'flag_emoji' => '🇸🇾',
                'capital' => 'Damascus',
                'latitude' => 34.8021,
                'longitude' => 38.9968,
                'is_active' => true,
                'is_visible' => true,
                'display_order' => 1,
            ]
        );

        $states = [
            ['name' => 'Damascus Governorate', 'name_ar' => 'محافظة دمشق', 'lat' => 33.5138, 'lng' => 36.2765],
            ['name' => 'Rural Damascus', 'name_ar' => 'ريف دمشق', 'lat' => 33.4500, 'lng' => 36.4500],
            ['name' => 'Aleppo', 'name_ar' => 'حلب', 'lat' => 36.2021, 'lng' => 37.1343],
            ['name' => 'Homs', 'name_ar' => 'حمص', 'lat' => 34.7298, 'lng' => 36.7090],
            ['name' => 'Hama', 'name_ar' => 'حماه', 'lat' => 35.1318, 'lng' => 36.7558],
            ['name' => 'Latakia', 'name_ar' => 'اللاذقية', 'lat' => 35.5407, 'lng' => 35.7706],
            ['name' => 'Tartus', 'name_ar' => 'طرطوس', 'lat' => 34.8959, 'lng' => 35.8866],
            ['name' => 'Idlib', 'name_ar' => 'إدلب', 'lat' => 35.9333, 'lng' => 36.6333],
            ['name' => 'Daraa', 'name_ar' => 'درعا', 'lat' => 32.6189, 'lng' => 36.1021],
            ['name' => 'As-Suwayda', 'name_ar' => 'السويداء', 'lat' => 32.7090, 'lng' => 36.5694],
            ['name' => 'Quneitra', 'name_ar' => 'القنيطرة', 'lat' => 33.1259, 'lng' => 35.8244],
            ['name' => 'Deir ez-Zor', 'name_ar' => 'دير الزور', 'lat' => 35.3294, 'lng' => 40.1467],
            ['name' => 'Al-Hasakah', 'name_ar' => 'الحسكة', 'lat' => 36.5024, 'lng' => 40.7477],
            ['name' => 'Raqqa', 'name_ar' => 'الرقة', 'lat' => 35.9594, 'lng' => 39.0094],
        ];

        foreach ($states as $i => $s) {
            State::updateOrCreate(
                ['country_id' => $syria->id, 'name' => $s['name']],
                [
                    'name_ar' => $s['name_ar'],
                    'type' => 'governorate',
                    'latitude' => $s['lat'],
                    'longitude' => $s['lng'],
                    'is_active' => true,
                    'is_visible' => true,
                    'display_order' => $i + 1,
                ]
            );
        }

        $this->seedDamascusCities($syria, State::where('country_id', $syria->id)->where('name', 'Damascus Governorate')->first());
        $this->seedRuralDamascusCities($syria, State::where('country_id', $syria->id)->where('name', 'Rural Damascus')->first());
        $this->seedOtherCities($syria);
    }

    private function seedDamascusCities(Country $country, ?State $state): void
    {
        if (! $state) {
            return;
        }
        $cities = [
            ['name' => 'Damascus', 'name_ar' => 'دمشق', 'lat' => 33.5138, 'lng' => 36.2765, 'popular' => true],
            ['name' => 'Mazzeh', 'name_ar' => 'المزة', 'lat' => 33.5024, 'lng' => 36.2447, 'popular' => true],
            ['name' => 'Kafarsouseh', 'name_ar' => 'كفرسوسة', 'lat' => 33.5021, 'lng' => 36.2551, 'popular' => true],
            ['name' => 'Sahnaya', 'name_ar' => 'صحنايا', 'lat' => 33.4389, 'lng' => 36.2222],
            ['name' => 'Old City Damascus', 'name_ar' => 'دمشق القديمة', 'lat' => 33.5117, 'lng' => 36.3081],
            ['name' => 'Salhiyeh', 'name_ar' => 'الصالحية', 'lat' => 33.5183, 'lng' => 36.2892, 'popular' => true],
        ];
        $this->insertCities($country, $state, $cities);
    }

    private function seedRuralDamascusCities(Country $country, ?State $state): void
    {
        if (! $state) {
            return;
        }
        $cities = [
            ['name' => 'Jaramana', 'name_ar' => 'جرمانا', 'lat' => 33.4859, 'lng' => 36.3447, 'popular' => true],
            ['name' => 'Daraya', 'name_ar' => 'داريا', 'lat' => 33.4583, 'lng' => 36.2417, 'popular' => true],
            ['name' => 'Douma', 'name_ar' => 'دوما', 'lat' => 33.5722, 'lng' => 36.4031],
            ['name' => 'Yalda', 'name_ar' => 'يلدا', 'lat' => 33.4600, 'lng' => 36.3200],
            ['name' => 'Qudsaya', 'name_ar' => 'قدسيا', 'lat' => 33.5453, 'lng' => 36.2056],
            ['name' => 'Harasta', 'name_ar' => 'حرستا', 'lat' => 33.5667, 'lng' => 36.3667],
            ['name' => 'Sayyida Zaynab', 'name_ar' => 'السيدة زينب', 'lat' => 33.4448, 'lng' => 36.3393, 'popular' => true],
            ['name' => 'Adra', 'name_ar' => 'عدرا', 'lat' => 33.6389, 'lng' => 36.5042],
            ['name' => 'Qatana', 'name_ar' => 'قطنا', 'lat' => 33.4361, 'lng' => 36.0833],
        ];
        $this->insertCities($country, $state, $cities);
    }

    private function seedOtherCities(Country $country): void
    {
        $citiesByState = [
            'Aleppo' => [
                ['name' => 'Aleppo', 'name_ar' => 'حلب', 'lat' => 36.2021, 'lng' => 37.1343, 'popular' => true],
                ['name' => 'Azaz', 'name_ar' => 'أعزاز', 'lat' => 36.5870, 'lng' => 37.0473],
            ],
            'Homs' => [
                ['name' => 'Homs', 'name_ar' => 'حمص', 'lat' => 34.7298, 'lng' => 36.7090, 'popular' => true],
                ['name' => 'Palmyra', 'name_ar' => 'تدمر', 'lat' => 34.5567, 'lng' => 38.2843],
            ],
            'Latakia' => [
                ['name' => 'Latakia', 'name_ar' => 'اللاذقية', 'lat' => 35.5407, 'lng' => 35.7706, 'popular' => true],
                ['name' => 'Jableh', 'name_ar' => 'جبلة', 'lat' => 35.3608, 'lng' => 35.9230],
            ],
            'Hama' => [
                ['name' => 'Hama', 'name_ar' => 'حماه', 'lat' => 35.1318, 'lng' => 36.7558, 'popular' => true],
            ],
            'Tartus' => [
                ['name' => 'Tartus', 'name_ar' => 'طرطوس', 'lat' => 34.8959, 'lng' => 35.8866, 'popular' => true],
            ],
            'Daraa' => [
                ['name' => 'Daraa', 'name_ar' => 'درعا', 'lat' => 32.6189, 'lng' => 36.1021, 'popular' => true],
            ],
        ];

        foreach ($citiesByState as $stateName => $cities) {
            $state = State::where('country_id', $country->id)->where('name', $stateName)->first();
            if (! $state) {
                continue;
            }
            $this->insertCities($country, $state, $cities);
        }
    }

    private function insertCities(Country $country, State $state, array $cities): void
    {
        foreach ($cities as $i => $c) {
            City::updateOrCreate(
                ['state_id' => $state->id, 'name' => $c['name']],
                [
                    'country_id' => $country->id,
                    'name_ar' => $c['name_ar'],
                    'latitude' => $c['lat'],
                    'longitude' => $c['lng'],
                    'is_active' => true,
                    'is_visible' => true,
                    'is_popular' => $c['popular'] ?? false,
                    'display_order' => $i + 1,
                ]
            );
        }
    }
}
