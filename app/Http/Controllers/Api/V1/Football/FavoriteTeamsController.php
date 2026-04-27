<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Exceptions\Football\TeamAlreadyFavoritedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Football\AddFavoriteTeamRequest;
use App\Http\Requests\Api\V1\Football\ReorderFavoriteTeamsRequest;
use App\Http\Resources\Football\TeamResource;
use App\Http\Traits\ApiResponse;
use App\Services\Football\FavoritesService;
use App\Services\Football\FootballDataApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteTeamsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FavoritesService $favorites,
        private readonly FootballDataApiService $api,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teams = $request->user()->favoriteTeams()->with('league')->get();

        return $this->success(TeamResource::collection($teams));
    }

    public function store(AddFavoriteTeamRequest $request): JsonResponse
    {
        try {
            $team = $this->favorites->addFavoriteTeam(
                $request->user(),
                (int) $request->input('team_id'),
                (bool) $request->boolean('notify_matches', true),
                (bool) $request->boolean('notify_goals', false),
                (bool) $request->boolean('notify_results', true),
            );
        } catch (TeamAlreadyFavoritedException $e) {
            return $this->error($e->getMessage(), null, 409);
        }

        return $this->success(new TeamResource($team), __('Team added to favorites'), 201);
    }

    public function destroy(Request $request, int $teamId): JsonResponse
    {
        $this->favorites->removeFavoriteTeam($request->user(), $teamId);

        return $this->success(null, __('Team removed from favorites'));
    }

    public function reorder(ReorderFavoriteTeamsRequest $request): JsonResponse
    {
        $this->favorites->reorderFavoriteTeams($request->user(), $request->input('team_ids', []));

        return $this->success(null, __('Favorites reordered'));
    }

    public function matchesToday(Request $request): JsonResponse
    {
        $externalIds = $request->user()->favoriteTeams()->pluck('external_id')->toArray();
        $matches = collect($this->api->getTodayMatches()['matches'] ?? [])
            ->filter(fn ($m) => in_array((string) ($m['homeTeam']['id'] ?? ''), $externalIds, true)
                || in_array((string) ($m['awayTeam']['id'] ?? ''), $externalIds, true))
            ->values()
            ->all();

        return $this->success([
            'date' => today()->format('Y-m-d'),
            'matches' => $matches,
            'total' => count($matches),
        ]);
    }

    public function matchesUpcoming(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 7);
        $days = max(1, min($days, 30));
        $externalIds = $request->user()->favoriteTeams()->pluck('external_id')->toArray();
        $matches = collect($this->api->getUpcomingMatches($days)['matches'] ?? [])
            ->filter(fn ($m) => in_array((string) ($m['homeTeam']['id'] ?? ''), $externalIds, true)
                || in_array((string) ($m['awayTeam']['id'] ?? ''), $externalIds, true))
            ->values()
            ->all();

        return $this->success([
            'matches' => $matches,
            'total' => count($matches),
            'days' => $days,
        ]);
    }
}
