<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\GoogleSignInRequest;
use App\Http\Requests\Api\V1\Auth\LoginOtpSendRequest;
use App\Http\Requests\Api\V1\Auth\LoginOtpVerifyRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\FirebaseAuthService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserRepositoryInterface $userRepo,
        private OtpService $otpService,
        private FirebaseAuthService $firebaseAuth,
    ) {}

    /**
     * Send OTP to the given phone via WhatsApp/SMS.
     */
    public function sendOtp(LoginOtpSendRequest $request): JsonResponse
    {
        $uuid = $this->otpService->send(
            phoneNumber: $request->phone,
            ipAddress: $request->ip(),
            channel: $request->input('channel'),
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
     * Resend an OTP for an existing challenge.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_uuid' => ['required', 'string', 'uuid'],
        ]);

        try {
            $uuid = $this->otpService->resend($data['challenge_uuid']);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'challenge_uuid' => $uuid,
                'expires_in_seconds' => 120,
            ],
        ]);
    }

    /**
     * Verify OTP. Auto-registers the user if no account exists for the phone.
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

        [$user, $isNewUser] = DB::transaction(function () use ($request) {
            $user = User::firstOrCreate(
                ['phone_number' => $request->phone],
                [
                    'country_code' => '+963',
                    'phone_verified_at' => now(),
                ],
            );

            $isNewUser = $user->wasRecentlyCreated;

            if ($isNewUser) {
                $user->assignRole('player');
            }

            if (is_null($user->phone_verified_at)) {
                $user->update(['phone_verified_at' => now()]);
            }

            if ($request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

            if ($request->filled('device_id') && $request->filled('fcm_token')) {
                $user->devices()->updateOrCreate(
                    ['device_id' => $request->device_id],
                    [
                        'fcm_token' => $request->fcm_token,
                        'platform' => $request->input('platform', 'android'),
                        'last_used_at' => now(),
                    ],
                );
            }

            return [$user, $isNewUser];
        });

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event($isNewUser ? 'registered_via_otp' : 'logged_in_via_otp')
            ->log($isNewUser ? 'New user registered via OTP' : 'User logged in via OTP');

        AuditLog::record(
            action: 'auth.login',
            userId: $user->id,
            subject: $user,
            changes: ['is_new_user' => $isNewUser, 'method' => 'otp'],
            ip: $request->ip(),
        );

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => [
                'is_new_user' => $isNewUser,
                'user_exists' => ! $isNewUser,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
                'user' => ! $isNewUser ? new UserResource($user) : null,
                'onboarding_prefill' => $isNewUser
                    ? ['name' => null, 'email' => null, 'avatar_url' => null]
                    : null,
            ],
        ]);
    }

    /**
     * Google Sign-In via Firebase ID token. Auto-registers new users.
     */
    public function googleSignIn(GoogleSignInRequest $request): JsonResponse
    {
        try {
            $claims = $this->firebaseAuth->verifyIdToken($request->id_token);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => __('auth.google_token_invalid'),
                'errors' => ['id_token' => [$e->getMessage()]],
            ], 401);
        }

        [$user, $isNewUser] = DB::transaction(function () use ($claims, $request) {
            $user = User::where('firebase_uid', $claims['uid'])->first();

            if (! $user && ! empty($claims['email'])) {
                $user = User::where('email', $claims['email'])->first();
            }

            $isNewUser = false;

            if (! $user) {
                $user = User::create([
                    'name' => $claims['name'] ?? null,
                    'email' => $claims['email'] ?? null,
                    'firebase_uid' => $claims['uid'],
                    'firebase_provider' => 'google.com',
                ]);
                $user->assignRole('player');
                $isNewUser = true;

                $user->socialIdentities()->create([
                    'provider' => 'google',
                    'provider_uid' => $claims['uid'],
                    'provider_email' => $claims['email'] ?? null,
                ]);
            } elseif (is_null($user->firebase_uid)) {
                $user->update([
                    'firebase_uid' => $claims['uid'],
                    'firebase_provider' => 'google.com',
                ]);
            }

            if ($request->fcm_token) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }

            if ($request->filled('device_id') && $request->filled('fcm_token')) {
                $user->devices()->updateOrCreate(
                    ['device_id' => $request->device_id],
                    [
                        'fcm_token' => $request->fcm_token,
                        'platform' => $request->input('platform', 'android'),
                        'last_used_at' => now(),
                    ],
                );
            }

            return [$user, $isNewUser];
        });

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event($isNewUser ? 'registered_via_google' : 'logged_in_via_google')
            ->log($isNewUser ? 'New user registered via Google' : 'User logged in via Google');

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'is_new_user' => $isNewUser,
                'user_exists' => ! $isNewUser,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
                'user' => ! $isNewUser ? new UserResource($user) : null,
                'onboarding_prefill' => $isNewUser ? [
                    'name' => $claims['name'] ?? null,
                    'email' => $claims['email'] ?? null,
                    'avatar_url' => $claims['picture'] ?? null,
                ] : null,
            ],
        ]);
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:'.now()->subYears(120)->toDateString()],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $data['name'],
            'date_of_birth' => $data['date_of_birth'],
            'onboarding_completed_at' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('profile_completed')
            ->log('User completed profile');

        return response()->json([
            'success' => true,
            'data' => ['user' => new UserResource($user->fresh())],
        ]);
    }

    /**
     * Issue a new Sanctum token and revoke the current one.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('auth.token_refreshed'),
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
            ],
        ]);
    }

    /**
     * Revoke the current access token only.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->noContent(__('auth.logged_out'));
    }

    /**
     * Revoke every access token for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->noContent(__('auth.logged_out_all'));
    }

    /**
     * List all active Sanctum sessions (tokens) for the user.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = (int) $user->currentAccessToken()->id;

        $tokens = $user->tokens()->orderByDesc('last_used_at')->get();

        $sessions = $tokens->map(fn ($token) => [
            'id' => (int) $token->id,
            'name' => $this->humanizeTokenName($token->name),
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'is_current' => (int) $token->id === $currentTokenId,
            'device_type' => $this->detectDeviceType($token->name),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $sessions,
                'meta' => [
                    'total_active_sessions' => $sessions->count(),
                    'current_session_id' => $currentTokenId,
                ],
            ],
        ]);
    }

    public function revokeSession(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = (int) $user->currentAccessToken()->id;

        if ($id === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الجلسة الحالية. استخدم تسجيل الخروج',
            ], 422);
        }

        $token = $user->tokens()->find($id);

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'الجلسة غير موجودة',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الجلسة',
        ]);
    }

    private function humanizeTokenName(?string $name): string
    {
        if (empty($name) || $name === 'auth_token') {
            return 'جهاز غير معروف';
        }

        return str_replace(['-', '_'], ' ', $name);
    }

    private function detectDeviceType(?string $name): string
    {
        if (! $name) {
            return 'unknown';
        }

        $lower = strtolower($name);

        return match (true) {
            str_contains($lower, 'iphone'), str_contains($lower, 'ipad'), str_contains($lower, 'ios') => 'mobile',
            str_contains($lower, 'android'), str_contains($lower, 'samsung') => 'mobile',
            str_contains($lower, 'web'), str_contains($lower, 'browser') => 'web',
            default => 'mobile',
        };
    }
}
