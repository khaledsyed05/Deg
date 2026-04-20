<?php

namespace App\Http\Controllers\Api\Club\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Club\V1\Staff\InviteStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\Club;
use App\Services\Club\ClubStaffService;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function __construct(
        private ClubStaffService $staffService,
    ) {}

    /**
     * List club staff
     *
     * Returns all staff members (managers) for the club. Club managers only.
     *
     * @response 200 {"success": true, "data": [{"id": 5, "name": "أحمد المدير"}]}
     * @response 403 {"message": "Forbidden"}
     */
    public function index(Club $club): JsonResponse
    {
        $this->authorize('manageStaff', $club);

        $staff = $this->staffService->list($club);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($staff),
        ]);
    }

    /**
     * Invite a staff member
     *
     * Attaches the authenticated user as a manager to the club. Club managers only.
     *
     * @response 201 {"success": true, "message": "تم دعوة الموظف"}
     * @response 403 {"message": "Forbidden"}
     */
    public function store(InviteStaffRequest $request, Club $club): JsonResponse
    {
        $this->authorize('manageStaff', $club);

        $this->staffService->attach($club, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => __('club.staff_invited'),
        ], 201);
    }

    /**
     * Remove a staff member
     *
     * Detaches a user from club staff. Club managers only.
     *
     * @urlParam userId integer required The user ID to remove. Example: 5
     *
     * @response 200 {"success": true}
     * @response 403 {"message": "Forbidden"}
     */
    public function destroy(Club $club, int $userId): JsonResponse
    {
        $this->authorize('manageStaff', $club);

        $this->staffService->detach($club, $userId);

        return response()->json(['success' => true]);
    }
}
