<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Profile\PhoneChangeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\InitiatePhoneChangeRequest;
use App\Http\Requests\Api\V1\Profile\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadAvatarRequest;
use App\Http\Requests\Api\V1\Profile\VerifyPhoneChangeRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Profile\PhoneChangeService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ApiResponse;

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
        return $this->success(new UserResource(auth()->user()));
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

        return $this->success(new UserResource($user->fresh()));
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
    /**
     * Upload avatar
     *
     * Replaces the authenticated user's avatar image (jpg/png, max 5MB).
     *
     * @response 200 {"success": true, "data": {"avatar_url": "https://..."}}
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->clearMediaCollection('avatar');
        $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return $this->success(['avatar_url' => $user->getAvatarUrl()]);
    }

    /**
     * Delete avatar
     *
     * Removes the authenticated user's avatar image.
     *
     * @response 200 {"success": true, "message": "Avatar deleted"}
     */
    public function deleteAvatar(): JsonResponse
    {
        auth()->user()->clearMediaCollection('avatar');

        return response()->json([
            'success' => true,
            'message' => __('auth.avatar_deleted'),
        ]);
    }

    public function initiatePhoneChange(InitiatePhoneChangeRequest $request, PhoneChangeService $service): JsonResponse
    {
        try {
            $changeRequest = $service->initiate(
                $request->user(),
                $request->validated('new_phone_number'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (PhoneChangeException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'phone_change_request_id' => $changeRequest->id,
            'new_phone_number_masked' => $service->maskPhone($changeRequest->new_phone),
            'otp_expires_in_seconds' => max(0, (int) now()->diffInSeconds($changeRequest->otp_expires_at, false)),
        ], 'تم إرسال رمز التحقق إلى الرقم الجديد');
    }

    public function verifyPhoneChange(VerifyPhoneChangeRequest $request, PhoneChangeService $service): JsonResponse
    {
        try {
            $user = $service->verify(
                $request->user(),
                (int) $request->validated('phone_change_request_id'),
                $request->validated('otp'),
            );
        } catch (PhoneChangeException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'phone_number' => $user->phone_number,
        ], 'تم تغيير رقم الموبايل بنجاح');
    }

    public function updateNotifications(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $this->userRepo->update(auth()->user(), [
            'notification_preferences' => array_merge(
                auth()->user()->notification_preferences ?? [],
                $request->validated(),
            ),
        ]);

        return $this->success(new UserResource($user));
    }
}
