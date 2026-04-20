<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Club;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $damascus  = City::where('name', 'Damascus')->firstOrFail()->id;
        $aleppo    = City::where('name', 'Aleppo')->firstOrFail()->id;
        $homs      = City::where('name', 'Homs')->firstOrFail()->id;
        $latakia   = City::where('name', 'Latakia')->firstOrFail()->id;
        $jableh    = City::where('name', 'Jableh')->firstOrFail()->id;
        $deirEzZor = City::where('name', 'Deir ez-Zor')->firstOrFail()->id;

        $clubs = [
            [
                'name'         => ['ar' => 'نادي الجلاء',    'en' => 'Al-Jaish Club'],
                'slug'         => 'al-jaish-club',
                'city_id'      => $damascus,
                'phone_number' => '+963112123456',
                'address'      => 'منطقة الفيحاء، دمشق',
                'latitude'     => 33.51380000,
                'longitude'    => 36.27650000,
                'status'       => 'active',
                'approved_at'  => now(),
                'is_featured'  => true,
            ],
            [
                'name'         => ['ar' => 'نادي الوثبة',    'en' => 'Al-Wathba Club'],
                'slug'         => 'al-wathba-club',
                'city_id'      => $homs,
                'phone_number' => '+963312345678',
                'address'      => 'شارع الحميدية، حمص',
                'latitude'     => 34.73200000,
                'longitude'    => 36.72500000,
                'status'       => 'active',
                'approved_at'  => now(),
                'is_featured'  => false,
            ],
            [
                'name'         => ['ar' => 'نادي الاتحاد',   'en' => 'Al-Ittihad Club'],
                'slug'         => 'al-ittihad-club',
                'city_id'      => $aleppo,
                'phone_number' => '+963212345678',
                'address'      => 'حي الجميلية، حلب',
                'latitude'     => 36.20220000,
                'longitude'    => 37.16170000,
                'status'       => 'active',
                'approved_at'  => now(),
                'is_featured'  => true,
            ],
            [
                'name'         => ['ar' => 'نادي تشرين',     'en' => 'Tishreen Club'],
                'slug'         => 'tishreen-club',
                'city_id'      => $latakia,
                'phone_number' => '+963412345678',
                'address'      => 'شارع بغداد، اللاذقية',
                'latitude'     => 35.53110000,
                'longitude'    => 35.79160000,
                'status'       => 'active',
                'approved_at'  => now(),
                'is_featured'  => false,
            ],
            [
                'name'         => ['ar' => 'نادي الكرامة',   'en' => 'Al-Karamah Club'],
                'slug'         => 'al-karamah-club',
                'city_id'      => $homs,
                'phone_number' => '+963312987654',
                'address'      => 'حي الوعر، حمص',
                'latitude'     => 34.75000000,
                'longitude'    => 36.71000000,
                'status'       => 'active',
                'approved_at'  => now(),
                'is_featured'  => false,
            ],
            [
                'name'         => ['ar' => 'نادي جبلة',      'en' => 'Jableh Club'],
                'slug'         => 'jableh-club',
                'city_id'      => $jableh,
                'phone_number' => '+963412987654',
                'address'      => 'وسط مدينة جبلة',
                'latitude'     => 35.36300000,
                'longitude'    => 35.92300000,
                'status'       => 'pending_approval',
                'approved_at'  => null,
                'is_featured'  => false,
            ],
            [
                'name'         => ['ar' => 'نادي الفتوة',    'en' => 'Al-Futowa Club'],
                'slug'         => 'al-futowa-club',
                'city_id'      => $deirEzZor,
                'phone_number' => '+963512345678',
                'address'      => 'وسط مدينة دير الزور',
                'latitude'     => 35.33600000,
                'longitude'    => 40.14000000,
                'status'       => 'pending_approval',
                'approved_at'  => null,
                'is_featured'  => false,
            ],
        ];

        foreach ($clubs as $data) {
            Club::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('✓ Clubs seeded: ' . Club::count() . ' clubs (5 active, 2 pending)');
    }
}
