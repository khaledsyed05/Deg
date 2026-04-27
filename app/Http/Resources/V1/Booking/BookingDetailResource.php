<?php

namespace App\Http\Resources\V1\Booking;

use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\RemainingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $venue = $this->venue;
        $club = $venue?->club;

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'qr_code' => $this->qr_code,
            'status' => $this->status?->value,
            'venue' => $venue ? [
                'id' => $venue->id,
                'slug' => $venue->slug,
                'name' => $venue->name,
                'image_url' => $venue->getFirstMediaUrl('images') ?: null,
                'location' => [
                    'latitude' => $venue->latitude !== null ? (float) $venue->latitude : null,
                    'longitude' => $venue->longitude !== null ? (float) $venue->longitude : null,
                ],
                'city' => $club?->city ? [
                    'id' => $club->city->id,
                    'name' => $club->city->name,
                    'name_ar' => $club->city->name_ar,
                ] : null,
                'club' => $club ? [
                    'id' => $club->id,
                    'name' => $club->name,
                    'phone' => $club->phone_number,
                    'whatsapp' => $club->whatsapp_number,
                    'email' => $club->email,
                    'address' => $club->address,
                ] : null,
            ] : null,
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'duration_minutes' => (int) $this->duration_minutes,
            'duration_hours' => round($this->duration_minutes / 60, 2),
            'pricing' => [
                'venue_price' => (int) $this->venue_price,
                'subtotal' => (int) $this->venue_price,
                'discount_amount' => (int) $this->discount_amount,
                'total_price' => (int) $this->total_price,
                'deposit_amount' => (int) $this->deposit_amount,
                'remaining_amount' => (int) $this->remaining_amount,
                'deposit_status' => $this->deposit_status?->value,
                'remaining_status' => $this->remaining_status?->value,
                'deposit_paid' => $this->deposit_status === DepositStatus::Paid,
                'remaining_paid' => $this->remaining_status === RemainingStatus::Confirmed,
                'currency' => $this->currency ?? 'SYP',
            ],
            'promotion' => $this->promotion_id ? [
                'id' => $this->promotion_id,
                'code' => $this->promotion?->code,
            ] : null,
            'notes' => $this->notes ?? null,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation' => $this->status === BookingStatus::Cancelled ? [
                'reason' => $this->cancellation_reason,
                'cancellation_fee' => (int) $this->cancellation_commission,
            ] : null,
            'can_cancel' => $this->canBeCancelled(),
            'cancellation_preview' => $this->canBeCancelled() ? [
                'cancellation_fee' => $this->calculateCancellationFee(),
                'refund_amount' => $this->refundAmount(),
            ] : null,
            'is_today' => $this->isToday(),
            'is_past' => $this->isPast(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
