<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Football\ApiSportsKeyManager;
use App\Services\Football\ApiSportsLiveScoreService;
use App\Services\Football\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveScoreController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MatchService $matchService,
        private readonly ApiSportsLiveScoreService $liveScore,
    ) {}

    public function liveMatches(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getLiveMatches($request->user()));
    }

    public function liveMatchesFavorites(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getLiveMatches($request->user(), favoritesOnly: true));
    }

    public function show(int $fixtureId): JsonResponse
    {
        $match = $this->liveScore->getMatchLive($fixtureId);
        if (! $match) {
            return $this->notFound(__('Match not found'));
        }

        return $this->success($match);
    }

    public function events(int $fixtureId): JsonResponse
    {
        return $this->success(['events' => $this->liveScore->getMatchEvents($fixtureId)]);
    }

    public function statistics(int $fixtureId): JsonResponse
    {
        return $this->success(['statistics' => $this->liveScore->getMatchStatistics($fixtureId)]);
    }

    public function lineups(int $fixtureId): JsonResponse
    {
        return $this->success(['lineups' => $this->liveScore->getMatchLineups($fixtureId)]);
    }

    public function quotaStatus(Request $request, ApiSportsKeyManager $keyManager): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorized();
        }
        if (! $user->hasRole('admin')) {
            return $this->forbidden(__('Admin access required'));
        }

        return $this->success([
            'keys' => $keyManager->getAllKeysStatus(),
            'has_keys' => $keyManager->hasKeys(),
        ]);
    }
}
