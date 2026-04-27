<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Resources\Football\LeagueResource;
use App\Http\Traits\ApiResponse;
use App\Models\Football\League;
use App\Services\Football\FootballDataApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaguesController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FootballDataApiService $api) {}

    public function index(Request $request): JsonResponse
    {
        $query = League::query()->active();

        if ($request->boolean('featured')) {
            $query->featured();
        } else {
            $query->orderByDesc('is_featured')->orderBy('display_order')->orderBy('name');
        }

        return $this->success(LeagueResource::collection($query->get()));
    }

    public function show(string $code): JsonResponse
    {
        $league = League::where('code', $code)->firstOrFail();

        return $this->success(new LeagueResource($league));
    }

    public function standings(string $code): JsonResponse
    {
        return $this->success($this->api->getLeagueStandings($code));
    }

    public function scorers(string $code, Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 10);

        return $this->success($this->api->getLeagueScorers($code, $limit));
    }

    public function matches(string $code): JsonResponse
    {
        return $this->success($this->api->getLeagueMatches($code));
    }

    public function schedule(string $code): JsonResponse
    {
        return $this->success($this->api->getLeagueMatches($code, 'SCHEDULED'));
    }
}
