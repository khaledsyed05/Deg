<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class GeographySeeder extends Seeder
{
    // Major cities to make visible by default
    private const VISIBLE_CITIES = [
        'Damascus', 'Aleppo', 'Homs', 'Latakia', 'Hama', 'Tartus',
        'دمشق', 'حلب', 'حمص', 'اللاذقية', 'حماة', 'طرطوس',
    ];

    public function run(): void
    {
        $country = Country::firstOrCreate(
            ['iso2' => 'SY'],
            [
                'iso3'       => 'SYR',
                'name'       => 'Syria',
                'name_ar'    => 'سوريا',
                'phone_code' => '+963',
                'capital'    => 'Damascus',
                'currency'   => 'SYP',
                'latitude'   => 34.80207500,
                'longitude'  => 38.99681600,
                'is_active'  => true,
            ]
        );

        foreach ($this->getGovernorates() as $governorate) {
            $state = State::firstOrCreate(
                ['country_id' => $country->id, 'name' => $governorate['name']],
                [
                    'country_id' => $country->id,
                    'name'       => $governorate['name'],
                    'name_ar'    => $governorate['name_ar'],
                    'state_code' => $governorate['code'],
                    'latitude'   => $governorate['lat'],
                    'longitude'  => $governorate['lng'],
                    'is_active'  => true,
                ]
            );

            foreach ($governorate['cities'] as $cityName) {
                $isVisible = in_array($cityName['name'], self::VISIBLE_CITIES)
                    || in_array($cityName['name_ar'], self::VISIBLE_CITIES);

                City::firstOrCreate(
                    ['state_id' => $state->id, 'name' => $cityName['name']],
                    [
                        'state_id'  => $state->id,
                        'name'      => $cityName['name'],
                        'name_ar'   => $cityName['name_ar'],
                        'latitude'  => $cityName['lat'],
                        'longitude' => $cityName['lng'],
                        'is_active' => $isVisible,
                    ]
                );
            }
        }

        $this->command->info('✓ Geography seeded: 1 country, 14 governorates, ' . City::count() . ' cities');
    }

    /** @return array<int, array{name: string, name_ar: string, code: string, lat: float, lng: float, cities: array<int, array{name: string, name_ar: string, lat: float, lng: float}>}> */
    private function getGovernorates(): array
    {
        return [
            [
                'name'    => 'Damascus',
                'name_ar' => 'دمشق',
                'code'    => 'DI',
                'lat'     => 33.51000000,
                'lng'     => 36.29128000,
                'cities'  => [
                    ['name' => 'Damascus',    'name_ar' => 'دمشق',    'lat' => 33.51000000, 'lng' => 36.29128000],
                    ['name' => 'Darayya',     'name_ar' => 'داريا',   'lat' => 33.46000000, 'lng' => 36.23000000],
                    ['name' => 'Al Tall',     'name_ar' => 'التل',    'lat' => 33.60500000, 'lng' => 36.30500000],
                ],
            ],
            [
                'name'    => 'Rif Dimashq',
                'name_ar' => 'ريف دمشق',
                'code'    => 'RD',
                'lat'     => 33.55000000,
                'lng'     => 36.61500000,
                'cities'  => [
                    ['name' => 'Duma',         'name_ar' => 'دوما',       'lat' => 33.56900000, 'lng' => 36.40100000],
                    ['name' => 'Douma',        'name_ar' => 'دوما',       'lat' => 33.57000000, 'lng' => 36.40000000],
                    ['name' => 'Qudsaya',      'name_ar' => 'قدسيا',      'lat' => 33.54600000, 'lng' => 36.22900000],
                    ['name' => 'Zabadani',     'name_ar' => 'الزبداني',   'lat' => 33.72300000, 'lng' => 36.09700000],
                    ['name' => 'Yabroud',      'name_ar' => 'يبرود',      'lat' => 33.96700000, 'lng' => 36.66700000],
                    ['name' => 'Nabk',         'name_ar' => 'النبك',      'lat' => 34.02600000, 'lng' => 36.72900000],
                ],
            ],
            [
                'name'    => 'Aleppo',
                'name_ar' => 'حلب',
                'code'    => 'HL',
                'lat'     => 36.20220000,
                'lng'     => 37.16170000,
                'cities'  => [
                    ['name' => 'Aleppo',    'name_ar' => 'حلب',      'lat' => 36.20220000, 'lng' => 37.16170000],
                    ['name' => 'Al-Bab',    'name_ar' => 'الباب',    'lat' => 36.37300000, 'lng' => 37.51900000],
                    ['name' => 'Manbij',    'name_ar' => 'منبج',     'lat' => 36.51200000, 'lng' => 37.94500000],
                    ['name' => 'Afrin',     'name_ar' => 'عفرين',    'lat' => 36.51300000, 'lng' => 36.86800000],
                    ['name' => 'Azaz',      'name_ar' => 'أعزاز',    'lat' => 36.58700000, 'lng' => 37.05100000],
                ],
            ],
            [
                'name'    => 'Homs',
                'name_ar' => 'حمص',
                'code'    => 'HI',
                'lat'     => 34.73190000,
                'lng'     => 36.72420000,
                'cities'  => [
                    ['name' => 'Homs',         'name_ar' => 'حمص',         'lat' => 34.73190000, 'lng' => 36.72420000],
                    ['name' => 'Talbisah',     'name_ar' => 'تلبيسة',      'lat' => 34.82400000, 'lng' => 36.73100000],
                    ['name' => 'Al-Rastan',    'name_ar' => 'الرستن',       'lat' => 34.91900000, 'lng' => 36.73100000],
                    ['name' => 'Qusayr',       'name_ar' => 'القصير',       'lat' => 34.51200000, 'lng' => 36.57900000],
                    ['name' => 'Tadmur',       'name_ar' => 'تدمر',        'lat' => 34.55000000, 'lng' => 38.26700000],
                ],
            ],
            [
                'name'    => 'Hama',
                'name_ar' => 'حماة',
                'code'    => 'HA',
                'lat'     => 35.13220000,
                'lng'     => 36.75150000,
                'cities'  => [
                    ['name' => 'Hama',       'name_ar' => 'حماة',       'lat' => 35.13220000, 'lng' => 36.75150000],
                    ['name' => 'Masyaf',     'name_ar' => 'مصياف',      'lat' => 35.06500000, 'lng' => 36.34100000],
                    ['name' => 'Salamiyah',  'name_ar' => 'سلمية',      'lat' => 35.01200000, 'lng' => 37.04900000],
                    ['name' => 'Suqaylabiyah', 'name_ar' => 'سقيلبية',  'lat' => 35.36700000, 'lng' => 36.38300000],
                ],
            ],
            [
                'name'    => 'Latakia',
                'name_ar' => 'اللاذقية',
                'code'    => 'LA',
                'lat'     => 35.53111000,
                'lng'     => 35.79161000,
                'cities'  => [
                    ['name' => 'Latakia',  'name_ar' => 'اللاذقية', 'lat' => 35.53111000, 'lng' => 35.79161000],
                    ['name' => 'Jableh',   'name_ar' => 'جبلة',     'lat' => 35.36300000, 'lng' => 35.92300000],
                    ['name' => 'Baniyas',  'name_ar' => 'بانياس',   'lat' => 35.17400000, 'lng' => 35.94800000],
                    ['name' => 'Qardaha',  'name_ar' => 'القرداحة',  'lat' => 35.56100000, 'lng' => 35.97200000],
                ],
            ],
            [
                'name'    => 'Tartus',
                'name_ar' => 'طرطوس',
                'code'    => 'TA',
                'lat'     => 34.88821000,
                'lng'     => 35.88659000,
                'cities'  => [
                    ['name' => 'Tartus',     'name_ar' => 'طرطوس',   'lat' => 34.88821000, 'lng' => 35.88659000],
                    ['name' => 'Baniyas',    'name_ar' => 'بانياس',  'lat' => 35.18200000, 'lng' => 35.95100000],
                    ['name' => 'Safita',     'name_ar' => 'صافيتا',  'lat' => 34.82100000, 'lng' => 36.11100000],
                    ['name' => 'Dreikish',   'name_ar' => 'دريكيش',  'lat' => 34.91300000, 'lng' => 36.14200000],
                ],
            ],
            [
                'name'    => 'Idlib',
                'name_ar' => 'إدلب',
                'code'    => 'ID',
                'lat'     => 35.93040000,
                'lng'     => 36.63390000,
                'cities'  => [
                    ['name' => 'Idlib',      'name_ar' => 'إدلب',    'lat' => 35.93040000, 'lng' => 36.63390000],
                    ['name' => 'Jisr al-Shughur', 'name_ar' => 'جسر الشغور', 'lat' => 35.81600000, 'lng' => 36.31900000],
                    ['name' => 'Ariha',      'name_ar' => 'أريحا',   'lat' => 35.80100000, 'lng' => 36.60500000],
                    ['name' => 'Maarat al-Numan', 'name_ar' => 'معرة النعمان', 'lat' => 35.64300000, 'lng' => 36.67600000],
                ],
            ],
            [
                'name'    => 'Deir ez-Zor',
                'name_ar' => 'دير الزور',
                'code'    => 'DZ',
                'lat'     => 35.33600000,
                'lng'     => 40.14000000,
                'cities'  => [
                    ['name' => 'Deir ez-Zor',  'name_ar' => 'دير الزور',  'lat' => 35.33600000, 'lng' => 40.14000000],
                    ['name' => 'Mayadin',       'name_ar' => 'الميادين',   'lat' => 35.01800000, 'lng' => 40.45500000],
                    ['name' => 'Abu Kamal',     'name_ar' => 'أبو كمال',   'lat' => 34.45100000, 'lng' => 40.91700000],
                ],
            ],
            [
                'name'    => 'Hasakah',
                'name_ar' => 'الحسكة',
                'code'    => 'HA',
                'lat'     => 36.48410000,
                'lng'     => 40.74890000,
                'cities'  => [
                    ['name' => 'Hasakah',   'name_ar' => 'الحسكة',   'lat' => 36.48410000, 'lng' => 40.74890000],
                    ['name' => 'Qamishli',  'name_ar' => 'القامشلي',  'lat' => 37.05100000, 'lng' => 41.22700000],
                    ['name' => 'Ras al-Ayn', 'name_ar' => 'رأس العين', 'lat' => 36.86800000, 'lng' => 40.06600000],
                ],
            ],
            [
                'name'    => 'Raqqa',
                'name_ar' => 'الرقة',
                'code'    => 'RA',
                'lat'     => 35.95500000,
                'lng'     => 39.00900000,
                'cities'  => [
                    ['name' => 'Raqqa',     'name_ar' => 'الرقة',   'lat' => 35.95500000, 'lng' => 39.00900000],
                    ['name' => 'Tabqa',     'name_ar' => 'الطبقة',  'lat' => 35.85300000, 'lng' => 38.56700000],
                    ['name' => 'Tel Abyad', 'name_ar' => 'تل أبيض', 'lat' => 36.69500000, 'lng' => 38.95800000],
                ],
            ],
            [
                'name'    => 'Daraa',
                'name_ar' => 'درعا',
                'code'    => 'DR',
                'lat'     => 32.62300000,
                'lng'     => 36.10300000,
                'cities'  => [
                    ['name' => 'Daraa',        'name_ar' => 'درعا',      'lat' => 32.62300000, 'lng' => 36.10300000],
                    ['name' => 'Nawa',         'name_ar' => 'نوى',       'lat' => 32.88500000, 'lng' => 36.04300000],
                    ['name' => "Izraa",        'name_ar' => 'إزرع',      'lat' => 32.85300000, 'lng' => 36.24700000],
                    ['name' => 'Al-Sanamayn',  'name_ar' => 'الصنمين',   'lat' => 33.07600000, 'lng' => 36.18300000],
                ],
            ],
            [
                'name'    => 'As-Suwayda',
                'name_ar' => 'السويداء',
                'code'    => 'SU',
                'lat'     => 32.70900000,
                'lng'     => 36.56600000,
                'cities'  => [
                    ['name' => 'As-Suwayda',  'name_ar' => 'السويداء',  'lat' => 32.70900000, 'lng' => 36.56600000],
                    ['name' => 'Shahba',      'name_ar' => 'شهبا',      'lat' => 32.85100000, 'lng' => 36.63200000],
                    ['name' => 'Salkhad',     'name_ar' => 'صلخد',      'lat' => 32.49200000, 'lng' => 36.71100000],
                ],
            ],
            [
                'name'    => 'Quneitra',
                'name_ar' => 'القنيطرة',
                'code'    => 'QU',
                'lat'     => 33.12500000,
                'lng'     => 35.82400000,
                'cities'  => [
                    ['name' => 'Quneitra',  'name_ar' => 'القنيطرة', 'lat' => 33.12500000, 'lng' => 35.82400000],
                    ['name' => 'Fiq',       'name_ar' => 'فيق',      'lat' => 32.77700000, 'lng' => 35.68700000],
                    ['name' => 'Khan Arnabah', 'name_ar' => 'خان أرنبة', 'lat' => 33.08600000, 'lng' => 35.88200000],
                ],
            ],
        ];
    }
}
