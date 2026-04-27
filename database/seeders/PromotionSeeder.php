<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'code' => 'WELCOME20',
                'name' => ['ar' => 'خصم الترحيب', 'en' => 'Welcome Discount'],
                'description' => ['ar' => 'خصم 20% على أول حجز', 'en' => '20% off your first booking'],
                'type' => 'percentage',
                'value' => 20,
                'max_discount' => 100000,
                'applies_to' => 'all',
                'is_featured' => true,
                'first_booking_only' => true,
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addMonths(3),
                'max_uses' => 1000,
                'max_uses_per_user' => 1,
            ],
            [
                'code' => 'SUMMER30',
                'name' => ['ar' => 'صيف حار', 'en' => 'Hot Summer'],
                'description' => ['ar' => 'خصم 30% على جميع الحجوزات', 'en' => '30% off all bookings'],
                'type' => 'percentage',
                'value' => 30,
                'max_discount' => 150000,
                'applies_to' => 'all',
                'is_featured' => true,
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addMonths(2),
                'max_uses' => 500,
                'max_uses_per_user' => 1,
            ],
            [
                'code' => 'WEEKEND50K',
                'name' => ['ar' => 'عطلة نهاية الأسبوع', 'en' => 'Weekend Deal'],
                'description' => ['ar' => 'خصم 50,000 ل.س على حجوزات عطلة نهاية الأسبوع', 'en' => '50,000 SYP off weekend bookings'],
                'type' => 'fixed_amount',
                'value' => 50000,
                'min_amount' => 200000,
                'applies_to' => 'all',
                'allowed_days' => ['friday', 'saturday'],
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addMonth(),
                'max_uses_per_user' => 2,
            ],
        ];

        foreach ($promotions as $data) {
            Promotion::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'status' => 'active',
                    'current_uses' => 0,
                ]),
            );
        }

        Venue::query()->limit(5)->get()->each(function (Venue $venue) {
            $code = 'VIP'.Str::upper(Str::random(4));

            Promotion::firstOrCreate(
                ['code' => $code],
                [
                    'club_id' => $venue->club_id,
                    'venue_id' => $venue->id,
                    'name' => ['ar' => 'عرض خاص - '.($venue->getTranslation('name', 'ar') ?: $venue->slug)],
                    'description' => ['ar' => 'خصم حصري لهذا الملعب'],
                    'type' => 'percentage',
                    'value' => 15,
                    'applies_to' => 'all',
                    'status' => 'active',
                    'valid_from' => now()->subDay(),
                    'valid_to' => now()->addWeeks(2),
                    'max_uses' => 100,
                    'max_uses_per_user' => 1,
                    'current_uses' => 0,
                ],
            );
        });

        $this->command?->info('Promotions seeded.');
    }
}
