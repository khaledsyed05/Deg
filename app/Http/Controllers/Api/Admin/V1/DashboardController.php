<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private ClubRepositoryInterface $clubRepo,
        private UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Admin dashboard stats
     *
     * Returns platform-wide statistics. Admin only.
     *
     * @response 200 {"success": true, "data": {"total_users": 1500, "total_clubs": 45, "pending_clubs": 3}}
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $this->userRepo->query()->count(),
                'total_clubs' => $this->clubRepo->query()->count(),
                'pending_clubs' => $this->clubRepo->findPendingApproval()->count(),
            ],
        ]);
    }
}
