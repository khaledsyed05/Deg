<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@daqehjezly.sy'],
            [
                'name'           => 'خالد السيد',
                'phone_number'   => '+963944000001',
                'email'          => 'admin@daqehjezly.sy',
                'password'       => Hash::make('password'),
                'account_status' => 'active',
            ]
        );
        $admin->syncRoles(['admin']);

        // Club managers
        $managers = [
            [
                'name'         => 'محمد الأحمد',
                'phone_number' => '+963944000002',
                'email'        => 'manager.jaish@daqehjezly.sy',
                'club_slug'    => 'al-jaish-club',
            ],
            [
                'name'         => 'عمر خريبين',
                'phone_number' => '+963944000003',
                'email'        => 'manager.wathba@daqehjezly.sy',
                'club_slug'    => 'al-wathba-club',
            ],
            [
                'name'         => 'يوسف كلش',
                'phone_number' => '+963944000004',
                'email'        => 'manager.ittihad@daqehjezly.sy',
                'club_slug'    => 'al-ittihad-club',
            ],
            [
                'name'         => 'أحمد صالح',
                'phone_number' => '+963944000005',
                'email'        => 'manager.tishreen@daqehjezly.sy',
                'club_slug'    => 'tishreen-club',
            ],
            [
                'name'         => 'فراس الخطيب',
                'phone_number' => '+963944000006',
                'email'        => 'manager.karamah@daqehjezly.sy',
                'club_slug'    => 'al-karamah-club',
            ],
        ];

        foreach ($managers as $data) {
            $clubSlug = $data['club_slug'];
            unset($data['club_slug']);

            $manager = User::firstOrCreate(
                ['phone_number' => $data['phone_number']],
                array_merge($data, [
                    'password'       => Hash::make('password'),
                    'account_status' => 'active',
                ])
            );
            $manager->syncRoles(['club_manager']);

            // Attach to club
            $club = Club::where('slug', $clubSlug)->first();
            if ($club) {
                $club->update(['owner_id' => $manager->id]);
                $club->managers()->syncWithoutDetaching([$manager->id]);
            }
        }

        // Players (10 realistic Syrian names)
        $players = [
            ['name' => 'عبد الرحمن ويس',     'phone_number' => '+963944111111'],
            ['name' => 'ديبو فضل',            'phone_number' => '+963944111112'],
            ['name' => 'زهير المدلج',          'phone_number' => '+963944111113'],
            ['name' => 'عمار حمدان',           'phone_number' => '+963944111114'],
            ['name' => 'محمود المواس',         'phone_number' => '+963944111115'],
            ['name' => 'أيهم الأشعري',         'phone_number' => '+963944111116'],
            ['name' => 'طه الخطيب',            'phone_number' => '+963944111117'],
            ['name' => 'علاء الدين الكردي',    'phone_number' => '+963944111118'],
            ['name' => 'مهند الأسعد',          'phone_number' => '+963944111119'],
            ['name' => 'وسام السعد',           'phone_number' => '+963944111120'],
        ];

        foreach ($players as $data) {
            $player = User::firstOrCreate(
                ['phone_number' => $data['phone_number']],
                array_merge($data, [
                    'password'       => Hash::make('password'),
                    'account_status' => 'active',
                ])
            );
            $player->syncRoles(['player']);
        }

        $this->command->info(
            '✓ Users seeded: 1 admin, ' . count($managers) . ' club managers, ' . count($players) . ' players'
        );
    }
}
