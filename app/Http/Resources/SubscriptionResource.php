<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venue' => $this->whenLoaded('venue', fn () => [
                'id' => $this->venue->id,
                'slug' => $this->venue->slug,
                'name' => $this->venue->name,
            ]),
            'frequency' => $this->frequency->value,
            'frequency_label' => $this->frequency->label(),
            'interval' => $this->interval,
            'day_of_week' => $this->day_of_week,
            'day_of_month' => $this->day_of_month,
            'start_time' => $this->start_time,
            'duration_hours' => (int) $this->duration_hours,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'auto_pay' => (bool) $this->auto_pay,
            'payment_method_id' => $this->payment_method_id,
            'pricing' => [
                'price_per_booking' => (int) $this->price_per_booking,
                'discount_percentage' => (float) $this->discount_percentage,
                'savings_per_booking' => $this->getSavingsAmount(),
                'currency' => 'SYP',
            ],
            'next_booking_date' => $this->next_booking_date?->toDateString(),
            'next_charge_date' => $this->next_charge_date?->toDateString(),
            'total_bookings_created' => (int) $this->total_bookings_created,
            'can_pause' => $this->canBePaused(),
            'pause_count' => (int) $this->pause_count,
            'paused_at' => $this->paused_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
