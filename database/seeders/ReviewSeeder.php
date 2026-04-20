<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        Review::truncate();

        $eligibleBookings = Booking::with('venue.club')
            ->whereIn('status', ['completed', 'confirmed'])
            ->get();

        if ($eligibleBookings->isEmpty()) {
            $this->command->warn('No eligible bookings found — skipping ReviewSeeder');

            return;
        }

        $comments = [
            'ملعب ممتاز والأرضية نظيفة جداً',
            'تجربة رائعة، المرافق جيدة والموظفين متعاونين',
            'الملعب جيد لكن يحتاج إنارة أفضل',
            'خدمة ممتازة وسعر مناسب',
            'أرضية عشبية صناعية من الدرجة الأولى، سنعود مجدداً',
            'الحجز كان سهلاً والملعب على مستوى جيد',
            'تجربة ممتعة مع الأصدقاء، الملعب واسع ومريح',
            'الخدمة سريعة والتسهيلات ممتازة',
            'ملعب نظيف ومجهز بشكل جيد، أنصح به',
            'المكان جميل والأجواء رائعة، لكن التكييف ضعيف قليلاً',
            'أسعار معقولة مقارنةً بالجودة المقدمة',
            'الموظفون محترمون وسريعو الاستجابة',
            'ملعب رياضي بمواصفات جيدة في موقع ممتاز',
            'استمتعنا كثيراً، الأرضية آمنة ومناسبة للعب',
            'تجربة لا بأس بها، يمكن تحسين غرف تغيير الملابس',
        ];

        // Ratings weighted toward 4-5 stars
        $ratings = [3.0, 4.0, 4.0, 4.5, 4.5, 5.0, 5.0, 4.0, 4.5, 5.0, 3.5, 4.0, 5.0, 4.5, 4.0];

        // One review per (user_id, club_id) pair — deduplicate before inserting
        $seen = [];
        $commentIndex = 0;

        foreach ($eligibleBookings as $booking) {
            $clubId = $booking->venue?->club_id;

            if (! $clubId) {
                continue;
            }

            $key = "{$booking->user_id}:{$clubId}";
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            Review::create([
                'user_id'      => $booking->user_id,
                'club_id'      => $clubId,
                'booking_id'   => $booking->id,
                'rating'       => $ratings[$commentIndex % count($ratings)],
                'body'         => $comments[$commentIndex % count($comments)],
                'is_anonymous' => 0,
                'is_published' => 1,
                'published_at' => now(),
            ]);

            $commentIndex++;

            if ($commentIndex >= 15) {
                break;
            }
        }

        $this->command->info('✓ Reviews seeded: ' . Review::count() . ' reviews');
    }
}
