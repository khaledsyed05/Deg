<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'booking_code'          => $this->booking_code,
            'venue'                 => new VenueResource($this->whenLoaded('venue')),
            'booking_date'          => $this->booking_date,
            'start_time'            => $this->start_time,
            'end_time'              => $this->end_time,
            'duration_minutes'      => $this->duration_minutes,
            'venue_price'           => $this->venue_price,
            'total_price'           => $this->total_price,
            'deposit_amount'        => $this->deposit_amount,
            'deposit_status'        => $this->deposit_status,
            'remaining_amount'      => $this->remaining_amount,
            'remaining_status'      => $this->remaining_status,
            'status'                => $this->status,
            'payment_status'        => $this->payment_status,
            'source'                => $this->source,
            'notes'                 => $this->notes,
            'cancelled_at'          => $this->cancelled_at?->toISOString(),
            'cancellation_reason'   => $this->cancellation_reason,
            'created_at'            => $this->created_at->toISOString(),
        ];
    }
}
