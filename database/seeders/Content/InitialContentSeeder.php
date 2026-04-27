<?php

namespace Database\Seeders\Content;

use App\Models\Content\Banner;
use App\Models\Content\BlogArticle;
use App\Models\Content\Tip;
use App\Models\Content\Video;
use Illuminate\Database\Seeder;

class InitialContentSeeder extends Seeder
{
    public function run(): void
    {
        Banner::query()->updateOrCreate(
            ['title_ar' => 'ابدأ رحلتك الرياضية'],
            [
                'title' => 'Start your athletic journey',
                'subtitle_ar' => 'احجز ملعبك المفضل بضغطة زر',
                'subtitle' => 'Book your favorite venue in one tap',
                'image_url' => 'https://placehold.co/1080x540/png',
                'position' => 'home_top',
                'is_active' => true,
                'display_order' => 1,
            ]
        );

        $tips = [
            ['title_ar' => 'الإحماء قبل اللعب', 'content_ar' => 'احرص على إحماء عضلاتك 10 دقائق قبل بداية المباراة.', 'category' => 'training', 'icon_name' => 'flame'],
            ['title_ar' => 'شرب الماء', 'content_ar' => 'حافظ على الترطيب طوال المباراة بشرب الماء كل 15-20 دقيقة.', 'category' => 'nutrition', 'icon_name' => 'droplet'],
            ['title_ar' => 'الراحة بعد التمرين', 'content_ar' => 'تمدد لمدة 5-10 دقائق بعد التمرين لتجنب الشد العضلي.', 'category' => 'recovery', 'icon_name' => 'moon'],
        ];

        foreach ($tips as $i => $tip) {
            Tip::query()->updateOrCreate(
                ['title_ar' => $tip['title_ar']],
                array_merge($tip, ['display_order' => $i + 1, 'is_active' => true])
            );
        }

        Video::query()->updateOrCreate(
            ['title_ar' => 'كيف تستخدم تطبيق دق احجزلي'],
            [
                'description_ar' => 'دليل شامل لاستخدام التطبيق وحجز ملعبك المفضل.',
                'youtube_id' => 'dQw4w9WgXcQ',
                'category' => 'app_guide',
                'duration_seconds' => 180,
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        BlogArticle::query()->updateOrCreate(
            ['title' => 'Welcome to Daq Ehjizli'],
            [
                'title_ar' => 'مرحباً بك في دق احجزلي',
                'excerpt' => 'Find the best venues near you and book in seconds.',
                'excerpt_ar' => 'اعثر على أفضل الملاعب القريبة منك واحجز خلال ثوانٍ.',
                'content' => 'Detailed welcome content here.',
                'content_ar' => 'محتوى ترحيبي مفصل هنا.',
                'category' => 'news',
                'is_published' => true,
                'is_featured' => true,
                'published_at' => now(),
                'reading_time_minutes' => 3,
            ]
        );
    }
}
