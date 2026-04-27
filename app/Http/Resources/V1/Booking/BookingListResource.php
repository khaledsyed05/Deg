<?php

namespace App\Http\Resources\V1\Booking;

use App\Enums\DepositStatus;
use App\Enums\RemainingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $venue = $this->venue;

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status?->value,
            'venue' => $venue ? [
                'id' => $venue->id,
                'slug' => $venue->slug,
                'name' => $venue->name,
                'image_url' => $venue->getFirstMediaUrl('images') ?: null,
                'city' => $venue->club?->city ? [
                    'id' => $venue->club->city->id,
                    'name' => $venue->club->city->name,
                    'name_ar' => $venue->club->city->name_ar,
                ] : null,
            ] : null,
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => (int) $this->duration_minutes,
            'pricing' => [
                'total_price' => (int) $this->total_price,
                'deposit_amount' => (int) $this->deposit_amount,
                'remaining_amount' => (int) $this->remaining_amount,
                'discount_amount' => (int) $this->discount_amount,
                'currency' => $this->currency ?? 'SYP',
            ],
            'deposit_paid' => $this->deposit_status === DepositStatus::Paid,
            'remaining_paid' => $this->remaining_status === RemainingStatus::Confirmed,
            'can_cancel' => $this->canBeCancelled(),
            'is_today' => $this->isToday(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
