<?php

namespace App\Notifications\Football;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DailyMatchSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $matches) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->matches);

        return [
            'type' => 'football_daily_summary',
            'title' => '📅 ملخص مباريات اليوم',
            'body' => $count > 0
                ? "{$count} مباراة لفرقك المفضلة اليوم"
                : 'لا توجد مباريات لفرقك المفضلة اليوم',
            'matches_count' => $count,
            'matches' => $this->matches,
        ];
    }
}
