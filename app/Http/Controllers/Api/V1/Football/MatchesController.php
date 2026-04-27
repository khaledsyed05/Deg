<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Football\FootballDataApiService;
use App\Services\Football\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchesController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MatchService $matchService,
        private readonly FootballDataApiService $footballData,
    ) {}

    public function today(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getTodayMatches($request->user()));
    }

    public function todayFavorites(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getTodayMatches($request->user(), favoritesOnly: true));
    }

    public function upcoming(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 7);
        $days = max(1, min($days, 30));

        return $this->success($this->matchService->getUpcomingMatches($request->user(), days: $days));
    }

    public function upcomingFavorites(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 7);
        $days = max(1, min($days, 30));

        return $this->success($this->matchService->getUpcomingMatches($request->user(), favoritesOnly: true, days: $days));
    }

    public function yesterday(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getYesterdayResults($request->user()));
    }

    public function show(string $externalId): JsonResponse
    {
        $data = $this->footballData->getMatch($externalId);
        if (empty($data)) {
            return $this->notFound(__('Match not found'));
        }

        return $this->success($data);
    }
}
