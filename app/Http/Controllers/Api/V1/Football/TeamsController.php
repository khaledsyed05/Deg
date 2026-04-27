<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Resources\Football\TeamResource;
use App\Http\Traits\ApiResponse;
use App\Models\Football\League;
use App\Models\Football\Team;
use App\Services\Football\FootballDataApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FootballDataApiService $api) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        $query = Team::query()->with('league');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('short_name', 'like', "%{$q}%")
                    ->orWhere('tla', 'like', "%{$q}%");
            });
        }

        $teams = $query->orderByDesc('is_popular')->orderBy('popularity_rank')->limit(50)->get();

        return $this->success(TeamResource::collection($teams));
    }

    public function popular(): JsonResponse
    {
        $teams = Team::with('league')->popular()->limit(50)->get();

        return $this->success(TeamResource::collection($teams));
    }

    public function show(int $id): JsonResponse
    {
        $team = Team::with('league')->findOrFail($id);

        return $this->success(new TeamResource($team));
    }

    public function recentMatches(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        return $this->success($this->api->getTeamMatches((int) $team->external_id, 'FINISHED'));
    }

    public function upcomingMatches(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        return $this->success($this->api->getTeamMatches((int) $team->external_id, 'SCHEDULED'));
    }

    public function squad(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        return $this->success($this->api->getTeam((int) $team->external_id));
    }

    public function byLeague(string $code): JsonResponse
    {
        $league = League::where('code', $code)->firstOrFail();
        $teams = Team::with('league')->where('league_id', $league->id)->orderBy('name')->get();

        return $this->success(TeamResource::collection($teams));
    }
}
