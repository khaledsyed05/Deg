<?php

namespace App\Console\Commands\Football;

use App\Models\User;
use App\Notifications\Football\DailyMatchSummaryNotification;
use App\Services\Football\FootballDataApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('football:daily-summary')]
#[Description('Send daily match summary to users with favorite teams')]
class SendDailyMatchSummaryCommand extends Command
{
    public function handle(FootballDataApiService $api): int
    {
        $todayMatches = $api->getTodayMatches()['matches'] ?? [];

        $users = User::query()
            ->whereHas('matchNotificationSettings', fn ($q) => $q->where('daily_summary_enabled', true))
            ->whereHas('favoriteTeams')
            ->with('favoriteTeams')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $externalIds = $user->favoriteTeams->pluck('external_id')->toArray();
            $matches = array_values(array_filter($todayMatches, function ($m) use ($externalIds) {
                return in_array((string) ($m['homeTeam']['id'] ?? ''), $externalIds, true)
                    || in_array((string) ($m['awayTeam']['id'] ?? ''), $externalIds, true);
            }));

            $user->notify(new DailyMatchSummaryNotification($matches));
            $count++;
        }

        $this->info("Dispatched daily summary for {$count} users");

        return self::SUCCESS;
    }
}
