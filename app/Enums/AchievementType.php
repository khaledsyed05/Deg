<?php

namespace App\Enums;

enum AchievementType: string
{
    case FirstBooking = 'first_booking';
    case EarlyBird = 'early_bird';
    case NightOwl = 'night_owl';
    case LoyalPlayer = 'loyal_player';
    case VenueExplorer = 'venue_explorer';
    case StreakMaster = 'streak_master';
    case ReviewChampion = 'review_champion';
    case SocialButterfly = 'social_butterfly';
    case BigSpender = 'big_spender';

    /**
     * @return array{title: string, description: string, icon: string, points: int, target: int}
     */
    public function metadata(): array
    {
        return match ($this) {
            self::FirstBooking => [
                'title' => 'أول حجز',
                'description' => 'أكمل أول حجز لك',
                'icon' => 'trophy',
                'points' => 10,
                'target' => 1,
            ],
            self::EarlyBird => [
                'title' => 'الطائر المبكر',
                'description' => '10 حجوزات قبل الساعة 8 صباحاً',
                'icon' => 'sunrise',
                'points' => 25,
                'target' => 10,
            ],
            self::NightOwl => [
                'title' => 'بومة الليل',
                'description' => '10 حجوزات بعد الساعة 8 مساءً',
                'icon' => 'moon',
                'points' => 25,
                'target' => 10,
            ],
            self::LoyalPlayer => [
                'title' => 'لاعب مخلص',
                'description' => '50 حجز مكتمل',
                'icon' => 'star',
                'points' => 100,
                'target' => 50,
            ],
            self::VenueExplorer => [
                'title' => 'مستكشف الملاعب',
                'description' => 'احجز في 10 ملاعب مختلفة',
                'icon' => 'compass',
                'points' => 50,
                'target' => 10,
            ],
            self::StreakMaster => [
                'title' => 'سيد التتابع',
                'description' => 'سلسلة حجز 7 أيام متتالية',
                'icon' => 'fire',
                'points' => 75,
                'target' => 7,
            ],
            self::ReviewChampion => [
                'title' => 'بطل التقييمات',
                'description' => '25 تقييم مُقدم',
                'icon' => 'pen',
                'points' => 60,
                'target' => 25,
            ],
            self::SocialButterfly => [
                'title' => 'فراشة اجتماعية',
                'description' => '5 دعوات أصدقاء ناجحة',
                'icon' => 'users',
                'points' => 40,
                'target' => 5,
            ],
            self::BigSpender => [
                'title' => 'منفق كبير',
                'description' => 'أنفق 5 مليون ليرة سورية',
                'icon' => 'money',
                'points' => 200,
                'target' => 5000000,
            ],
        };
    }
}
