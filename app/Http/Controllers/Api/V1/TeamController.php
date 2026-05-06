<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Team\TeamException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Team\GenerateInviteRequest;
use App\Http\Requests\Api\V1\Team\KickMemberRequest;
use App\Http\Requests\Api\V1\Team\TransferCaptainRequest;
use App\Http\Requests\Api\V1\Team\UpdateTeamRequest;
use App\Http\Resources\Team\TeamInviteResource;
use App\Http\Resources\Team\TeamResource;
use App\Http\Traits\ApiResponse;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Services\Team\DeleteTeamService;
use App\Services\Team\GenerateInviteService;
use App\Services\Team\KickMemberService;
use App\Services\Team\LeaveTeamService;
use App\Services\Team\TeamService;
use App\Services\Team\TransferCaptainService;
use App\Services\Team\UseInviteService;
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

    public function update(int $id, UpdateTeamRequest $request): JsonResponse
    {
        $team = Team::findOrFail($id);

        $this->authorize('update', $team);

        $team->update($request->validated());

        return $this->success(new TeamResource($team->fresh()), __('team.team_updated'));
    }

    public function destroy(int $id, Request $request, DeleteTeamService $service): JsonResponse
    {
        $team = Team::findOrFail($id);

        $this->authorize('delete', $team);

        $service->delete($team);

        return $this->success(['team_id' => $id], __('team.team_deleted'));
    }

    public function kick(int $id, KickMemberRequest $request, KickMemberService $service): JsonResponse
    {
        $team = Team::findOrFail($id);

        $this->authorize('kick', $team);

        $service->kick($team, $request->user(), (int) $request->validated('member_id'));

        return $this->success([
            'team_id' => $team->id,
            'member_id' => (int) $request->validated('member_id'),
        ], __('team.member_kicked'));
    }

    public function transferCaptain(int $id, TransferCaptainRequest $request, TransferCaptainService $service): JsonResponse
    {
        $team = Team::findOrFail($id);

        $this->authorize('transferCaptain', $team);

        $updated = $service->transfer($team, $request->user(), (int) $request->validated('new_captain_id'));

        return $this->success(new TeamResource($updated), __('team.captain_transferred'));
    }

    public function generateInvite(int $id, GenerateInviteRequest $request, GenerateInviteService $service): JsonResponse
    {
        $team = Team::findOrFail($id);

        $this->authorize('invite', $team);

        $expiresAt = $request->filled('expires_at')
            ? new \DateTimeImmutable((string) $request->validated('expires_at'))
            : null;

        $invite = $service->generate(
            $team,
            $request->user(),
            $expiresAt,
            $request->filled('max_uses') ? (int) $request->validated('max_uses') : null,
        );

        return $this->success(new TeamInviteResource($invite), __('team.invite_created'), 201);
    }

    public function useInvite(string $code, Request $request, UseInviteService $service): JsonResponse
    {
        $invite = TeamInvite::where('code', $code)->first();

        if (! $invite) {
            return $this->error(__('team.invite_not_found'), null, 404);
        }

        $result = $service->use($invite, $request->user());

        $message = $result['already_member']
            ? __('team.already_member')
            : __('team.joined_via_invite');

        return $this->success([
            'team_id' => $result['team_id'],
            'already_member' => $result['already_member'],
        ], $message);
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
