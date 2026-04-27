<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Exceptions\Club\AlreadyFollowingException;
use App\Exceptions\Club\NotFollowingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Club\FollowClubRequest;
use App\Http\Traits\ApiResponse;
use App\Services\Club\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    use ApiResponse;

    public function __construct(private ClubService $clubService) {}

    public function show(int $id, Request $request): JsonResponse
    {
        return $this->success($this->clubService->getClubDetails($id, $request->user()));
    }

    public function venues(int $id, Request $request): JsonResponse
    {
        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success($this->clubService->getClubVenues($id, $perPage));
    }

    public function reviews(int $id, Request $request): JsonResponse
    {
        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success($this->clubService->getClubReviews($id, $perPage));
    }

    public function contact(int $id): JsonResponse
    {
        return $this->success($this->clubService->getClubContact($id));
    }

    public function follow(int $id, FollowClubRequest $request): JsonResponse
    {
        try {
            $follower = $this->clubService->followClub(
                $id,
                $request->user(),
                $request->boolean('notify_updates', true),
                $request->boolean('notify_events', true),
                $request->boolean('notify_promotions', true)
            );
        } catch (AlreadyFollowingException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'club_id' => $follower->club_id,
            'followed_at' => $follower->created_at?->toIso8601String(),
        ], 'تمت متابعة النادي بنجاح', 201);
    }

    public function unfollow(int $id, Request $request): JsonResponse
    {
        try {
            $this->clubService->unfollowClub($id, $request->user());
        } catch (NotFollowingException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([], 'تم إلغاء متابعة النادي');
    }

    public function followed(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success($this->clubService->getFollowedClubs($request->user(), $perPage));
    }

    public function feed(int $id, Request $request): JsonResponse
    {
        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $type = $request->string('type')->value() ?: null;

        $allowed = ['announcement', 'promotion', 'event', 'news', 'venue_update', 'maintenance'];
        if ($type && ! in_array($type, $allowed, true)) {
            return $this->validationError(['type' => 'نوع غير صحيح']);
        }

        return $this->success($this->clubService->getClubFeed($id, $perPage, $type));
    }
}
