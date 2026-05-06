<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Booking\BookingListResource;
use App\Http\Traits\ApiResponse;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Profile\AchievementsService;
use App\Services\Profile\PlayerStatsService;
use App\Services\SportsProfile\WeeklyActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportsProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PlayerStatsService $statsService,
        private AchievementsService $achievementsService,
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    /**
     * Aggregated profile composed from stats + achievements + the
     * 5 most recent past bookings.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = $this->statsService->statsArray($user);
        $achievementsPayload = $this->achievementsService->achievementsArray($user);

        $recent = $this->bookingRepo->query()
            ->forUser($user->id)
            ->with(['venue.club.city', 'venue.media'])
            ->past()
            ->limit(5)
            ->get();

        return $this->success([
            'stats' => $stats,
            'achievements' => $achievementsPayload['achievements'],
            'total_points' => $achievementsPayload['total_points'],
            'recent_bookings' => BookingListResource::collection($recent)->resolve(),
        ]);
    }

    public function weeklyActivity(
        Request $request,
        WeeklyActivityService $service,
    ): JsonResponse {
        return $this->success([
            'weeks' => $service->get($request->user()),
        ]);
    }
}
