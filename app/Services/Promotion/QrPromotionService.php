<?php

namespace App\Services\Promotion;

use App\Exceptions\Promotion\PromotionException;
use App\Models\Booking;
use App\Models\Promotion;
use App\Models\QrPromotionRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QrPromotionService
{
    /**
     * @return array<string, mixed>
     */
    public function redeem(string $qrCode, User $user, ?int $bookingId = null): array
    {
        $promotion = Promotion::where('qr_code', $qrCode)
            ->where('is_qr_promotion', true)
            ->where('status', 'active')
            ->first();

        if (! $promotion) {
            throw new PromotionException('رمز QR غير صحيح', 404);
        }

        if ($promotion->valid_to && $promotion->valid_to < now()) {
            throw new PromotionException('انتهت صلاحية العرض', 410);
        }

        if ($promotion->valid_from && $promotion->valid_from > now()) {
            throw new PromotionException('العرض لم يبدأ بعد');
        }

        if ((int) $promotion->qr_redemption_count >= (int) $promotion->qr_redemption_limit) {
            throw new PromotionException('تم استنفاد العرض', 410);
        }

        if (QrPromotionRedemption::where('promotion_id', $promotion->id)
            ->where('user_id', $user->id)
            ->exists()) {
            throw new PromotionException('تم استبدال هذا العرض مسبقاً');
        }

        return DB::transaction(function () use ($promotion, $user, $bookingId) {
            QrPromotionRedemption::create([
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'redeemed_at' => now(),
            ]);

            $promotion->increment('qr_redemption_count');

            $discountAmount = 0;
            if ($bookingId) {
                $booking = Booking::where('id', $bookingId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $discountAmount = $this->calculateDiscount($promotion, $booking);

                $newTotal = max(0, (int) $booking->total_price - (int) $discountAmount);
                $booking->update([
                    'applied_promotion_id' => $promotion->id,
                    'discount_amount' => $discountAmount,
                    'total_price' => $newTotal,
                ]);
            }

            return [
                'promotion' => [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'type' => $promotion->type,
                    'value' => (int) $promotion->value,
                    'code' => $promotion->code,
                ],
                'applied_to_booking_id' => $bookingId,
                'discount_amount' => (int) $discountAmount,
                'expires_at' => $promotion->valid_to?->toIso8601String(),
            ];
        });
    }

    public function calculateDiscount(Promotion $promotion, Booking $booking): int
    {
        $total = (int) $booking->total_price;
        $value = (int) $promotion->value;

        $raw = match ($promotion->type) {
            'percentage' => (int) round($total * ($value / 100)),
            'fixed_amount' => $value,
            default => 0,
        };

        if ($promotion->max_discount) {
            $raw = min($raw, (int) $promotion->max_discount);
        }

        return min($raw, $total);
    }
}
