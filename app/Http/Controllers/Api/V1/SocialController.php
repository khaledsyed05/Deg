<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    use ApiResponse;

    public function inviteFriend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'phone_numbers' => 'required|array|min:1|max:5',
            'phone_numbers.*' => 'string|max:20',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        if ($booking->user_id !== auth()->id()) {
            return $this->error('غير مصرح', null, 403);
        }

        // TODO: dispatch invitations via SMS/WhatsApp provider.

        return $this->success([
            'invited' => count($validated['phone_numbers']),
        ], 'تم إرسال الدعوات');
    }

    public function friendsBookings(): JsonResponse
    {
        // Friend system not yet implemented.
        return $this->success([]);
    }

    public function shareBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $booking = Booking::with('venue')->findOrFail($validated['booking_id']);

        if ($booking->user_id !== auth()->id()) {
            return $this->error('غير مصرح', null, 403);
        }

        $shareUrl = url('/bookings/'.$booking->booking_code);
        $shareText = sprintf(
            'انضم إلي في %s يوم %s',
            $booking->venue?->name ?? '',
            $booking->booking_date?->format('Y-m-d') ?? '',
        );

        return $this->success([
            'share_url' => $shareUrl,
            'share_text' => $shareText,
            'booking_code' => $booking->booking_code,
        ]);
    }

    public function generateReferralCode(): JsonResponse
    {
        $user = auth()->user();
        $code = $user->generateReferralCode();

        return $this->success([
            'referral_code' => $code,
            'referral_url' => url('/signup?ref='.$code),
            'referrals_count' => $user->referrals()->count(),
        ]);
    }
}
