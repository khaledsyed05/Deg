<?php

namespace Database\Seeders\App;

use App\Models\App\AppConfig;
use App\Models\App\AppVersion;
use App\Models\App\FeatureFlag;
use Illuminate\Database\Seeder;

class InitialAppMetadataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ios', 'android'] as $platform) {
            AppVersion::updateOrCreate(
                ['platform' => $platform, 'version' => '1.0.0'],
                [
                    'build_number' => 100,
                    'is_force_update' => false,
                    'is_active' => true,
                    'release_notes' => 'Initial release',
                    'release_notes_ar' => 'الإصدار الأول',
                    'released_at' => today(),
                ]
            );
        }

        $flags = [
            ['key' => 'football_livescore', 'name' => 'Football Livescore', 'is_enabled' => true],
            ['key' => 'wallet_topup', 'name' => 'Wallet Top-up', 'is_enabled' => true],
            ['key' => 'saved_cards', 'name' => 'Saved Cards', 'is_enabled' => false],
            ['key' => 'challenges_enabled', 'name' => 'Challenges', 'is_enabled' => false],
            ['key' => 'social_features', 'name' => 'Social Features', 'is_enabled' => true],
            ['key' => 'emergency_button', 'name' => 'Emergency Button', 'is_enabled' => true],
            ['key' => 'recurring_bookings', 'name' => 'Recurring Bookings', 'is_enabled' => true],
            ['key' => 'group_bookings', 'name' => 'Group Bookings', 'is_enabled' => true],
        ];
        foreach ($flags as $f) {
            FeatureFlag::updateOrCreate(['key' => $f['key']], $f);
        }

        $configs = [
            ['key' => 'support_email', 'value' => 'support@daqehjezly.com', 'is_public' => true],
            ['key' => 'support_whatsapp', 'value' => '+963991234567', 'is_public' => true],
            ['key' => 'min_booking_minutes_before', 'value' => 60, 'is_public' => true],
            ['key' => 'max_booking_days_ahead', 'value' => 30, 'is_public' => true],
            ['key' => 'default_currency', 'value' => 'SYP', 'is_public' => true],
            ['key' => 'social_media', 'value' => [
                'facebook' => 'https://facebook.com/daqehjezly',
                'instagram' => 'https://instagram.com/daqehjezly',
                'telegram' => 'https://t.me/daqehjezly',
            ], 'is_public' => true],
        ];
        foreach ($configs as $c) {
            AppConfig::updateOrCreate(['key' => $c['key']], $c);
        }
    }
}
