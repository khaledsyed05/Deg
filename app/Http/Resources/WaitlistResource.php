<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaitlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'venue_id'         => $this->venue_id,
            'booking_date'     => $this->booking_date?->toDateString(),
            'start_time'       => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'notified_at'      => $this->notified_at?->toISOString(),
            'expires_at'       => $this->expires_at?->toISOString(),
            'venue'            => new VenueResource($this->whenLoaded('venue')),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
