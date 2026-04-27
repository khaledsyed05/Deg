<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Team\TeamException;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Team;
use App\Services\Team\LeaveTeamService;
use App\Services\Team\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TeamService $teamService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:200',
            'type' => 'required|in:casual,regular,competitive',
            'sport_category_id' => 'required|integer|exists:venue_categories,id',
            'max_members' => 'nullable|integer|min:2|max:20',
            'is_public' => 'sometimes|boolean',
            'requires_approval' => 'sometimes|boolean',
            'regular_schedule' => 'nullable|array',
        ]);

        try {
            $team = $this->teamService->create($validated, auth()->user());

            return $this->success(
                $team->load(['sportCategory', 'captain', 'activeMembers.user']),
                'تم إنشاء الفريق بنجاح',
                201,
            );
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function index(): JsonResponse
    {
        $teams = auth()->user()->teams()
            ->with(['sportCategory', 'captain'])
            ->withCount(['activeMembers as active_members_count'])
            ->get();

        return $this->success($teams);
    }

    public function show(int $id): JsonResponse
    {
        $team = Team::with(['sportCategory', 'captain', 'activeMembers.user'])
            ->withCount(['activeMembers as active_members_count', 'bookings as bookings_count'])
            ->findOrFail($id);

        if (! $team->is_public && ! $team->hasMember(auth()->id())) {
            return $this->error('غير مصرح', null, 403);
        }

        return $this->success($team);
    }

    public function leave(int $id, Request $request, LeaveTeamService $service): JsonResponse
    {
        $reason = $request->input('reason');

        try {
            $service->leave($id, $request->user(), is_string($reason) ? $reason : null);
        } catch (TeamException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'team_id' => $id,
            'left_at' => now()->toIso8601String(),
        ], 'تم مغادرة الفريق');
    }
}
