<?php

namespace Database\Seeders\App;

use App\Models\AppEnvironment;
use App\Models\AppPlatform;
use Illuminate\Database\Seeder;

class AppPlatformsSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'platform_key' => 'android',
                'name' => ['en' => 'Android', 'ar' => 'أندرويد'],
                'latest_version' => '1.0.0',
                'minimum_required_version' => '1.0.0',
                'store_url' => 'https://play.google.com/store/apps/details?id=com.degehjizli.app',
                'direct_apk_url' => null,
                'direct_apk_enabled' => 0,
                'is_active' => 1,
                'order_column' => 1,
            ],
            [
                'platform_key' => 'ios',
                'name' => ['en' => 'iOS', 'ar' => 'آي أو إس'],
                'latest_version' => '1.0.0',
                'minimum_required_version' => '1.0.0',
                'store_url' => 'https://apps.apple.com/app/id000000000',
                'direct_apk_url' => null,
                'direct_apk_enabled' => 0,
                'is_active' => 1,
                'order_column' => 2,
            ],
        ];

        foreach ($platforms as $row) {
            $platform = AppPlatform::updateOrCreate(
                ['platform_key' => $row['platform_key']],
                $row,
            );

            AppEnvironment::updateOrCreate(
                ['platform_id' => $platform->id, 'name' => 'live'],
                [
                    'base_url' => 'https://yallaehjez.com/api/v1',
                    'is_active' => 1,
                ],
            );

            AppEnvironment::updateOrCreate(
                ['platform_id' => $platform->id, 'name' => 'stage'],
                [
                    'base_url' => 'https://stage.yallaehjez.com/api/v1',
                    'is_active' => 0,
                ],
            );
        }
    }
}
