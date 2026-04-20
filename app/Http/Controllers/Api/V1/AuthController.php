<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\GoogleSignInRequest;
use App\Http\Requests\Api\V1\Auth\LoginOtpSendRequest;
use App\Http\Requests\Api\V1\Auth\LoginOtpVerifyRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\FirebaseAuthService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private OtpService $otpService,
        private FirebaseAuthService $firebaseAuth,
    ) {}

    /**
     * Register a new user
     *
     * Creates a new player account with phone number and password.
     * Returns a Sanctum token for immediate authentication.
     *
     * @unauthenticated
     *
     * @response 201 {"success": true, "data": {"user": {"id": 1, "name": "محمد الأحمد", "phone_number": "+963944123456"}, "token": "1|abc..."}}
     * @response 422 {"success": false, "errors": {"phone_number": ["رقم الهاتف مطلوب"]}}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepo->create($request->validated());
        $user->assignRole('player');

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Send OTP
     *
     * Sends a one-time password via SMS to the provided phone number.
     * Rate limited to 3 requests per 10 minutes per phone number.
     *
     * @unauthenticated
     *
     * @response 200 {"success": true, "data": {"challenge_uuid": "550e8400-e29b-41d4-a716-446655440000", "expires_in_seconds": 120}}
     * @response 429 {"success": false, "message": "عدد الطلبات كثير جداً"}
     */
    public function sendOtp(LoginOtpSendRequest $request): JsonResponse
    {
        $uuid = $this->otpService->send(
            phoneNumber: $request->phone,
            ipAddress: $request->ip(),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'challenge_uuid' => $uuid,
                'expires_in_seconds' => 120,
            ],
        ]);
    }

    /**
     * Verify OTP and login
     *
     * Validates the OTP code and returns an authentication token.
     * Optionally stores the FCM token for push notifications.
     *
     * @unauthenticated
     *
     * @response 200 {"success": true, "data": {"user": {"id": 1, "name": "محمد الأحمد"}, "token": "1|abc..."}}
     * @response 401 {"success": false, "message": "رمز التحقق غير صحيح"}
     * @response 404 {"success": false, "message": "المستخدم غير موجود"}
     */
    public function verifyOtp(LoginOtpVerifyRequest $request): JsonResponse
    {
        $verified = $this->otpService->verify(
            uuid: $request->challenge_uuid,
            code: $request->otp,
        );

        if (! $verified) {
            return response()->json([
                'success' => false,
                'message' => __('auth.otp_invalid'),
            ], 401);
        }

        $user = $this->userRepo->findByPhone($request->phone);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.user_not_found'),
            ], 404);
        }

        if ($request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Google Sign-In
     *
     * Authenticates a user using a Firebase Google ID token.
     * Creates a new account automatically if the user doesn't exist.
     *
     * @unauthenticated
     *
     * @response 200 {"success": true, "data": {"user": {"id": 1, "name": "Mohammad"}, "token": "1|abc..."}}
     * @response 401 {"success": false, "message": "رمز Google غير صالح"}
     */
    public function googleSignIn(GoogleSignInRequest $request): JsonResponse
    {
        try {
            $claims = $this->firebaseAuth->verifyIdToken($request->id_token);
        } catch (RuntimeException) {
            return response()->json([
                'success' => false,
                'message' => __('auth.google_token_invalid'),
            ], 401);
        }

        $user = $this->userRepo->findByFirebaseUid($claims['uid']);

        if (! $user) {
            $user = $this->userRepo->create([
                'firebase_uid' => $claims['uid'],
                'email' => $claims['email'] ?? null,
                'name' => $claims['name'] ?? null,
                'firebase_provider' => 'google.com',
            ]);
        }

        if ($request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout
     *
     * Revokes all access tokens for the authenticated user.
     *
     * @response 200 {"success": true, "message": "تم تسجيل الخروج"}
     */
    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => __('auth.logged_out'),
        ]);
    }
}
