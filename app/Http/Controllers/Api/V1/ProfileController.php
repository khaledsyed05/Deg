<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
    ) {}

    /**
     * Get my profile
     *
     * Returns the authenticated user's profile information.
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "محمد الأحمد", "phone_number": "+963944123456", "email": null}}
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource(auth()->user()),
        ]);
    }

    /**
     * Update my profile
     *
     * Updates the authenticated user's profile. Supports avatar upload via multipart form.
     *
     * @bodyParam name string User's full name. Example: محمد الأحمد
     * @bodyParam email string Valid email address. Example: user@example.com
     * @bodyParam avatar file Avatar image file (jpg, png, max 2MB).
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "محمد الأحمد"}}
     * @response 422 {"success": false, "errors": {"email": ["البريد الإلكتروني غير صالح"]}}
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userRepo->update(auth()->user(), $request->validated());

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Update notification preferences
     *
     * Updates push notification preferences for the authenticated user.
     *
     * @bodyParam booking_confirmed boolean Notify when booking is confirmed. Example: true
     * @bodyParam booking_cancelled boolean Notify when booking is cancelled. Example: true
     * @bodyParam payment_received boolean Notify on payment receipt. Example: true
     *
     * @response 200 {"success": true, "data": {"id": 1}}
     */
    public function updateNotifications(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $this->userRepo->update(auth()->user(), [
            'notification_preferences' => array_merge(
                auth()->user()->notification_preferences ?? [],
                $request->validated(),
            ),
        ]);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }
}
