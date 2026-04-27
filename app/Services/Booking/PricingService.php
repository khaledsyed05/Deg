<?php

namespace App\Services\Booking;

use App\Models\Promotion;
use App\Models\Venue;

class PricingService
{
    /**
     * Calculate booking price based on venue.price_from and optional promo code.
     *
     * @return array{
     *     price_per_hour: int,
     *     duration_minutes: int,
     *     duration_hours: float,
     *     subtotal: int,
     *     promotion: ?array{code: string, type: string, value: int, discount_amount: int},
     *     discount_amount: int,
     *     total_price: int,
     *     deposit_percentage: int,
     *     deposit_amount: int,
     *     remaining_amount: int,
     *     currency: string
     * }
     */
    public function calculate(int $venueId, int $durationMinutes, ?string $promoCode = null): array
    {
        $venue = Venue::with('club')->findOrFail($venueId);

        $pricePerHour = (int) ($venue->price_from ?? 0);
        $durationHours = $durationMinutes / 60;
        $subtotal = (int) round($pricePerHour * $durationHours);

        [$promotion, $discountAmount] = $this->resolvePromotion($venue, $subtotal, $promoCode);

        $totalPrice = max(0, $subtotal - $discountAmount);
        $depositPercentage = (int) data_get($venue->club?->settings, 'booking_rules.deposit_percentage', 30);
        $depositAmount = (int) round($totalPrice * $depositPercentage / 100);
        $remainingAmount = $totalPrice - $depositAmount;

        return [
            'price_per_hour' => $pricePerHour,
            'duration_minutes' => $durationMinutes,
            'duration_hours' => $durationHours,
            'subtotal' => $subtotal,
            'promotion' => $promotion ? [
                'code' => $promotion->code,
                'type' => $promotion->type,
                'value' => (int) $promotion->value,
                'discount_amount' => $discountAmount,
            ] : null,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice,
            'deposit_percentage' => $depositPercentage,
            'deposit_amount' => $depositAmount,
            'remaining_amount' => $remainingAmount,
            'currency' => 'SYP',
        ];
    }

    /**
     * @return array{0: ?Promotion, 1: int}
     */
    private function resolvePromotion(Venue $venue, int $subtotal, ?string $code): array
    {
        if (! $code) {
            return [null, 0];
        }

        $promotion = Promotion::query()
            ->where('code', $code)
            ->where('status', 'active')
            ->where(function ($q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->where(function ($q) use ($venue): void {
                $q->where('applies_to', 'all')
                    ->orWhereHas('venues', fn ($q2) => $q2->where('venues.id', $venue->id))
                    ->orWhere('club_id', $venue->club_id);
            })
            ->first();

        if (! $promotion) {
            return [null, 0];
        }

        if ($promotion->min_amount && $subtotal < (int) $promotion->min_amount) {
            return [null, 0];
        }

        $discount = $promotion->type === 'percentage'
            ? (int) round($subtotal * (int) $promotion->value / 100)
            : min((int) $promotion->value, $subtotal);

        if ($promotion->max_discount) {
            $discount = min($discount, (int) $promotion->max_discount);
        }

        return [$promotion, $discount];
    }
}
