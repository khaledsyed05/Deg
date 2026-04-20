<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Club\ApproveClubRequest;
use App\Http\Requests\Api\Admin\V1\Club\RejectClubRequest;
use App\Http\Resources\ClubResource;
use App\Models\Club;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Services\Club\ClubApprovalService;
use Illuminate\Http\JsonResponse;

class ClubController extends Controller
{
    public function __construct(
        private ClubRepositoryInterface $clubRepo,
        private ClubApprovalService $approvalService,
    ) {}

    /**
     * List pending clubs
     *
     * Returns all clubs awaiting admin approval. Admin only.
     *
     * @response 200 {"success": true, "data": [{"id": 1, "name": "نادي النور", "status": "pending"}]}
     */
    public function index(): JsonResponse
    {
        $clubs = $this->clubRepo->findPendingApproval();

        return response()->json([
            'success' => true,
            'data' => ClubResource::collection($clubs),
        ]);
    }

    /**
     * Approve a club
     *
     * Approves a pending club registration. Admin only.
     *
     * @response 200 {"success": true, "data": {"id": 1, "status": "active"}}
     * @response 403 {"message": "Forbidden"}
     */
    public function approve(ApproveClubRequest $request, Club $club): JsonResponse
    {
        $this->authorize('approve', Club::class);

        $club = $this->approvalService->approve($club, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => new ClubResource($club),
        ]);
    }

    /**
     * Reject a club
     *
     * Rejects a pending club registration with a reason. Admin only.
     *
     * @bodyParam reason string required Rejection reason to communicate to the club owner. Example: المستندات غير مكتملة
     *
     * @response 200 {"success": true, "data": {"id": 1, "status": "rejected"}}
     * @response 403 {"message": "Forbidden"}
     */
    public function reject(RejectClubRequest $request, Club $club): JsonResponse
    {
        $this->authorize('reject', Club::class);

        $club = $this->approvalService->reject($club, $request->reason);

        return response()->json([
            'success' => true,
            'data' => new ClubResource($club),
        ]);
    }
}
