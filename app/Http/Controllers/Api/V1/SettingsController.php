<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdateSettingsRequest;
use App\Http\Resources\V1\Settings\SettingsResource;
use App\Http\Traits\ApiResponse;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    use ApiResponse;

    public function index(Request $request): SettingsResource
    {
        $user = $request->user();

        if (! $user->notificationSettings()->exists()) {
            NotificationSetting::createDefaultsForUser($user->id);
        }

        $user->load('notificationSettings');

        return new SettingsResource($user);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $updates = [];

        if ($request->filled('language')) {
            $updates['preferred_language'] = $request->input('language');
        }
        if ($request->filled('timezone')) {
            $updates['timezone'] = $request->input('timezone');
        }
        if ($request->has('privacy')) {
            $updates['privacy_settings'] = array_merge($user->getPrivacySettings(), $request->input('privacy', []));
        }
        if ($request->has('preferences')) {
            $updates['preferences'] = array_merge($user->getPreferences(), $request->input('preferences', []));
        }

        if (! empty($updates)) {
            $user->forceFill($updates)->save();
        }

        if (isset($updates['preferred_language'])) {
            app()->setLocale($updates['preferred_language']);
        }

        $user->load('notificationSettings');

        return $this->success(new SettingsResource($user), 'تم تحديث الإعدادات بنجاح');
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        $data = $request->validate(['language' => ['required', 'in:ar,en']]);

        $request->user()->setLanguage($data['language']);

        return $this->success(['language' => $data['language']], 'تم تغيير اللغة بنجاح');
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'profile_visibility' => ['sometimes', 'in:public,friends,private'],
            'show_phone_number' => ['sometimes', 'boolean'],
            'show_bookings' => ['sometimes', 'boolean'],
            'show_reviews' => ['sometimes', 'boolean'],
            'allow_marketing' => ['sometimes', 'boolean'],
            'data_sharing' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'privacy_settings' => array_merge($user->getPrivacySettings(), $data),
        ])->save();

        return $this->success($user->getPrivacySettings(), 'تم تحديث إعدادات الخصوصية');
    }

    public function requestDataExport(Request $request): JsonResponse
    {
        $user = $request->user();

        // TODO: dispatch a queued job to build the archive. For now we just acknowledge.
        activity()
            ->causedBy($user)
            ->event('data_export_requested')
            ->log('User requested GDPR data export');

        return $this->success([
            'email' => $user->email,
            'eta_hours' => 24,
        ], 'سيتم إرسال البيانات إلى بريدك الإلكتروني خلال 24 ساعة');
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $user = $request->user();

        if ($user->password) {
            if (empty($data['password']) || ! Hash::check($data['password'], $user->password)) {
                return $this->error('كلمة المرور غير صحيحة', null, 401);
            }
        }

        DB::transaction(function () use ($user): void {
            $user->bookings()
                ->whereIn('status', [BookingStatus::PendingPayment->value, BookingStatus::Confirmed->value, BookingStatus::Scheduled->value])
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'تم حذف الحساب',
                    'cancelled_by' => $user->id,
                ]);

            $user->tokens()->delete();
            $user->delete();
        });

        return $this->success(null, 'تم حذف الحساب بنجاح');
    }
}
