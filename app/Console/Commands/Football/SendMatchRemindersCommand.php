<?php

namespace App\Console\Commands\Football;

use App\Models\Football\MatchReminderSent;
use App\Models\User;
use App\Notifications\Football\MatchReminderNotification;
use App\Services\Football\FootballDataApiService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('football:reminders')]
#[Description('Send match reminders to favorite-team users')]
class SendMatchRemindersCommand extends Command
{
    public function handle(FootballDataApiService $api): int
    {
        $today = $api->getTodayMatches()['matches'] ?? [];
        $tomorrow = $api->getUpcomingMatches(1)['matches'] ?? [];
        $upcomingMatches = array_merge($today, $tomorrow);

        $sent = 0;
        foreach ($upcomingMatches as $match) {
            $kickOff = isset($match['utcDate']) ? Carbon::parse($match['utcDate']) : null;
            if (! $kickOff) {
                continue;
            }

            $minutesUntil = now()->diffInMinutes($kickOff, false);
            if ($minutesUntil < 0 || $minutesUntil > 75) {
                continue;
            }

            $type = null;
            if ($minutesUntil >= 55 && $minutesUntil <= 65) {
                $type = '1h_before';
            } elseif ($minutesUntil >= 12 && $minutesUntil <= 18) {
                $type = '15min_before';
            }
            if (! $type) {
                continue;
            }

            $sent += $this->dispatchReminders($match, $type);
        }

        $this->info("Sent {$sent} match reminders");

        return self::SUCCESS;
    }

    private function dispatchReminders(array $match, string $type): int
    {
        $homeId = (string) ($match['homeTeam']['id'] ?? '');
        $awayId = (string) ($match['awayTeam']['id'] ?? '');
        $externalIds = array_filter([$homeId, $awayId]);
        if (empty($externalIds)) {
            return 0;
        }
        $matchExternalId = (string) ($match['id'] ?? '');
        if ($matchExternalId === '') {
            return 0;
        }

        $users = User::query()
            ->whereHas('favoriteTeams', function ($q) use ($externalIds) {
                $q->whereIn('football_teams.external_id', $externalIds)
                    ->where('user_favorite_teams.notify_matches', true);
            })
            ->whereHas('matchNotificationSettings', fn ($q) => $q->where('match_reminders_enabled', true))
            ->whereDoesntHave('matchRemindersSent', function ($q) use ($matchExternalId, $type) {
                $q->where('match_external_id', $matchExternalId)
                    ->where('reminder_type', $type);
            })
            ->get();

        foreach ($users as $user) {
            $user->notify(new MatchReminderNotification($match, $type));
            MatchReminderSent::create([
                'user_id' => $user->id,
                'match_external_id' => $matchExternalId,
                'reminder_type' => $type,
                'sent_at' => now(),
            ]);
        }

        return $users->count();
    }
}
