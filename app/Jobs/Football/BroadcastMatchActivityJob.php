<?php

namespace App\Jobs\Football;

use App\Models\Football\MatchActivityToken;
use App\Services\Football\ApnsLiveActivityPusher;
use App\Services\Notification\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Broadcasts a match state change to every active Live Activity client
 * (iOS Live Activity via APNs, Android Live Update via FCM) opted into
 * the given fixture. Dispatched from PollLiveScoresCommand after a state
 * diff is observed; runs on the queue so the poller stays fast.
 *
 * @phpstan-type ContentState array{
 *   home_score:int,
 *   away_score:int,
 *   status_short:string,
 *   minute:int,
 *   home_team_name:string,
 *   away_team_name:string,
 *   league_name:string
 * }
 */
class BroadcastMatchActivityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @var array<int,int> */
    public array $backoff = [10, 30];

    private const FINAL_STATUSES = ['FT', 'AET', 'PEN'];

    /**
     * @param  ContentState  $state
     */
    public function __construct(
        public readonly string $fixtureExternalId,
        public readonly array $state,
    ) {}

    public function handle(ApnsLiveActivityPusher $apns, FcmService $fcm): void
    {
        $isFinal = in_array($this->state['status_short'] ?? '', self::FINAL_STATUSES, true);

        $apnsCount = $apns->pushUpdate(
            fixtureExternalId: $this->fixtureExternalId,
            contentState: [
                'home_score' => (int) ($this->state['home_score'] ?? 0),
                'away_score' => (int) ($this->state['away_score'] ?? 0),
                'status_short' => (string) ($this->state['status_short'] ?? ''),
                'minute' => (int) ($this->state['minute'] ?? 0),
            ],
            isFinal: $isFinal,
        );

        $androidCount = $this->pushToAndroid($fcm, $isFinal);

        Log::channel('football')->info('Match activity broadcast complete', [
            'fixture_external_id' => $this->fixtureExternalId,
            'apns' => $apnsCount,
            'fcm' => $androidCount,
            'status' => $this->state['status_short'] ?? null,
        ]);
    }

    private function pushToAndroid(FcmService $fcm, bool $isFinal): int
    {
        $tokens = MatchActivityToken::query()
            ->forFixture($this->fixtureExternalId)
            ->active()
            ->android()
            ->get();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $homeTeam = (string) ($this->state['home_team_name'] ?? '');
        $awayTeam = (string) ($this->state['away_team_name'] ?? '');
        $homeScore = (int) ($this->state['home_score'] ?? 0);
        $awayScore = (int) ($this->state['away_score'] ?? 0);

        $title = trim("{$homeTeam} {$homeScore} - {$awayScore} {$awayTeam}");
        $body = $isFinal
            ? __('Full time')
            : (string) ($this->state['status_short'] ?? '');

        $data = array_map('strval', [
            'type' => 'match_update',
            'fixture_external_id' => $this->fixtureExternalId,
            'home_team_name' => $homeTeam,
            'away_team_name' => $awayTeam,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status_short' => (string) ($this->state['status_short'] ?? ''),
            'minute' => (int) ($this->state['minute'] ?? 0),
            'league_name' => (string) ($this->state['league_name'] ?? ''),
            'is_final' => $isFinal ? '1' : '0',
        ]);

        $success = 0;
        foreach ($tokens as $token) {
            $sent = $fcm->sendToToken($token->push_token, $title, $body, $data);
            if ($sent) {
                $success++;
                $token->update(['last_updated_at' => now()]);
            }
        }

        return $success;
    }
}
