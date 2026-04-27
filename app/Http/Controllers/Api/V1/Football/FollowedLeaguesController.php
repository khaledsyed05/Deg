<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Exceptions\Football\LeagueAlreadyFollowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Football\FollowLeagueRequest;
use App\Http\Requests\Api\V1\Football\ReorderFollowedLeaguesRequest;
use App\Http\Resources\Football\LeagueResource;
use App\Http\Traits\ApiResponse;
use App\Services\Football\FavoritesService;
use App\Services\Football\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowedLeaguesController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FavoritesService $favorites,
        private readonly MatchService $matchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $leagues = $request->user()->followedLeagues()->get();

        return $this->success(LeagueResource::collection($leagues));
    }

    public function store(FollowLeagueRequest $request): JsonResponse
    {
        try {
            $league = $this->favorites->followLeague(
                $request->user(),
                (string) $request->input('league_code'),
                (bool) $request->boolean('notify_matches', true),
            );
        } catch (LeagueAlreadyFollowedException $e) {
            return $this->error($e->getMessage(), null, 409);
        }

        return $this->success(new LeagueResource($league), __('League followed'), 201);
    }

    public function destroy(Request $request, int $leagueId): JsonResponse
    {
        $this->favorites->unfollowLeague($request->user(), $leagueId);

        return $this->success(null, __('League unfollowed'));
    }

    public function reorder(ReorderFollowedLeaguesRequest $request): JsonResponse
    {
        $this->favorites->reorderFollowedLeagues($request->user(), $request->input('league_ids', []));

        return $this->success(null, __('Followed leagues reordered'));
    }

    public function matches(Request $request): JsonResponse
    {
        return $this->success($this->matchService->getTodayMatches($request->user(), favoritesOnly: true));
    }
}
