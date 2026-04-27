<?php

namespace App\Services\Profile;

use App\Exceptions\Profile\PhoneChangeException;
use App\Models\PhoneChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PhoneChangeService
{
    public const MAX_HOURLY_ATTEMPTS = 3;

    public const MAX_OTP_ATTEMPTS = 5;

    public const OTP_TTL_MINUTES = 5;

    public function initiate(User $user, string $newPhone, ?string $ip = null, ?string $userAgent = null): PhoneChangeRequest
    {
        if ($user->phone_number === $newPhone) {
            throw new PhoneChangeException('الرقم الجديد مطابق للرقم الحالي');
        }

        if (User::where('phone_number', $newPhone)->where('id', '!=', $user->id)->exists()) {
            throw new PhoneChangeException('الرقم مستخدم بالفعل');
        }

        $recentAttempts = PhoneChangeRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentAttempts >= self::MAX_HOURLY_ATTEMPTS) {
            throw new PhoneChangeException(
                'تم تجاوز عدد المحاولات المسموح. حاول بعد ساعة',
                429
            );
        }

        return DB::transaction(function () use ($user, $newPhone, $ip, $userAgent) {
            PhoneChangeRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $isLocal = config('app.env') === 'local' || config('app.master_otp_enabled', false);
            $otp = $isLocal
                ? '123456'
                : str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $request = PhoneChangeRequest::create([
                'user_id' => $user->id,
                'current_phone' => $user->phone_number,
                'new_phone' => $newPhone,
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                'status' => 'pending',
                'ip_address' => $ip,
                'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            ]);

            Log::channel('security')->info('Phone change initiated', [
                'user_id' => $user->id,
                'new_phone_masked' => $this->maskPhone($newPhone),
                'request_id' => $request->id,
                'ip' => $ip,
            ]);

            return $request;
        });
    }

    public function verify(User $user, int $requestId, string $otp): User
    {
        $request = PhoneChangeRequest::where('id', $requestId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($request->otp_expires_at < now()) {
            $request->update(['status' => 'expired']);
            throw new PhoneChangeException('انتهت صلاحية رمز التحقق', 410);
        }

        if ($request->attempts >= self::MAX_OTP_ATTEMPTS) {
            $request->update(['status' => 'cancelled']);
            throw new PhoneChangeException('تم تجاوز عدد المحاولات المسموح');
        }

        if (! Hash::check($otp, $request->otp_hash)) {
            $request->increment('attempts');
            throw new PhoneChangeException('رمز التحقق غير صحيح');
        }

        return DB::transaction(function () use ($user, $request) {
            $user->update(['phone_number' => $request->new_phone]);

            $request->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            Log::channel('security')->info('Phone change completed', [
                'user_id' => $user->id,
                'request_id' => $request->id,
            ]);

            return $user->fresh();
        });
    }

    public function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 9) {
            return $phone;
        }

        return substr($phone, 0, 5).str_repeat('*', 4).substr($phone, -4);
    }
}
