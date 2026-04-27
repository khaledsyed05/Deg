<?php

namespace Database\Seeders\Events;

use App\Models\Club;
use App\Models\Event;
use Illuminate\Database\Seeder;

class SampleEventsSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = Club::query()->limit(5)->get();
        if ($clubs->isEmpty()) {
            $this->command?->warn('No clubs found — skipping SampleEventsSeeder.');

            return;
        }

        foreach ($clubs as $club) {
            $venue = $club->venues()->first();

            Event::query()->updateOrCreate(
                ['club_id' => $club->id, 'title_ar' => 'بطولة كرة القدم - الجمعة'],
                [
                    'venue_id' => $venue?->id,
                    'title' => 'Friday Football Tournament',
                    'description' => 'A weekly Friday tournament for amateur teams.',
                    'description_ar' => 'بطولة جمعة أسبوعية لكل الفرق الهاوية.',
                    'type' => 'tournament',
                    'sport_type' => 'football',
                    'starts_at' => now()->addWeek()->setHour(18),
                    'ends_at' => now()->addWeek()->setHour(22),
                    'registration_closes_at' => now()->addDays(5),
                    'max_participants' => 16,
                    'min_participants' => 8,
                    'registration_fee' => 25_000,
                    'participant_type' => 'team',
                    'team_size' => 7,
                    'prize_structure' => [
                        ['position' => 1, 'prize_type' => 'cash', 'value' => 200_000],
                        ['position' => 2, 'prize_type' => 'cash', 'value' => 100_000],
                        ['position' => 3, 'prize_type' => 'trophy', 'value' => 'كأس البرونز'],
                    ],
                    'rules_ar' => "1. كل فريق يتألف من 7 لاعبين\n2. مدة المباراة 30 دقيقة\n3. النتيجة بركلات الترجيح في حالة التعادل",
                    'status' => 'open',
                    'is_featured' => true,
                    'is_published' => true,
                ]
            );

            Event::query()->updateOrCreate(
                ['club_id' => $club->id, 'title_ar' => 'دورة تدريبية للمبتدئين'],
                [
                    'venue_id' => $venue?->id,
                    'title' => 'Beginner Training Camp',
                    'description_ar' => 'دورة تدريبية مكثفة للمبتدئين.',
                    'type' => 'training',
                    'sport_type' => 'football',
                    'starts_at' => now()->addDays(10)->setHour(17),
                    'ends_at' => now()->addDays(10)->setHour(20),
                    'registration_closes_at' => now()->addDays(8),
                    'max_participants' => 30,
                    'min_participants' => 5,
                    'registration_fee' => 0,
                    'participant_type' => 'individual',
                    'status' => 'open',
                    'is_published' => true,
                ]
            );
        }
    }
}
