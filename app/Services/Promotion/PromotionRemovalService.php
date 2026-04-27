<?php

namespace App\Services\Promotion;

use App\Exceptions\Promotion\PromotionException;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromotionRemovalService
{
    private const REMOVABLE_STATUSES = ['pending_payment', 'pending', 'pending_confirmation'];

    /**
     * @return array<string, mixed>
     */
    public function removeFromBooking(int $bookingId, User $user): array
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;

        if (! in_array($status, self::REMOVABLE_STATUSES, true)) {
            throw new PromotionException(
                'لا يمكن إزالة العرض من حجز مؤكد. يرجى إلغاء الحجز إذا أردت'
            );
        }

        if (empty($booking->applied_promotion_id)) {
            throw new PromotionException('لا يوجد عرض مطبّق على هذا الحجز');
        }

        return DB::transaction(function () use ($booking) {
            $previousTotal = (int) $booking->total_price;
            $discount = (int) ($booking->discount_amount ?? 0);
            $newTotal = $previousTotal + $discount;

            $booking->update([
                'applied_promotion_id' => null,
                'discount_amount' => 0,
                'total_price' => $newTotal,
            ]);

            return [
                'booking_id' => $booking->id,
                'previous_total' => $previousTotal,
                'new_total' => $newTotal,
            ];
        });
    }
}
