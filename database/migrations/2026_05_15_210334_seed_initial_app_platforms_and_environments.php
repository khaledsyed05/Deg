<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $platforms = [
            [
                'platform_key' => 'android',
                'name' => json_encode(['en' => 'Android', 'ar' => 'أندرويد'], JSON_UNESCAPED_UNICODE),
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
                'name' => json_encode(['en' => 'iOS', 'ar' => 'آي أو إس'], JSON_UNESCAPED_UNICODE),
                'latest_version' => '1.0.0',
                'minimum_required_version' => '1.0.0',
                'store_url' => 'https://apps.apple.com/app/id000000000',
                'direct_apk_url' => null,
                'direct_apk_enabled' => 0,
                'is_active' => 1,
                'order_column' => 2,
            ],
        ];

        foreach ($platforms as $platform) {
            $existing = DB::table('app_platforms')->where('platform_key', $platform['platform_key'])->first();
            if ($existing) {
                continue;
            }

            $id = DB::table('app_platforms')->insertGetId(array_merge($platform, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            DB::table('app_environments')->insert([
                [
                    'platform_id' => $id,
                    'name' => 'live',
                    'base_url' => 'https://yallaehjez.com/api/v1',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'platform_id' => $id,
                    'name' => 'stage',
                    'base_url' => 'https://stage.yallaehjez.com/api/v1',
                    'is_active' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('app_environments')
            ->whereIn('platform_id', function ($q) {
                $q->select('id')->from('app_platforms')->whereIn('platform_key', ['android', 'ios']);
            })
            ->delete();

        DB::table('app_platforms')->whereIn('platform_key', ['android', 'ios'])->delete();
    }
};
