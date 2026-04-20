<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'booking_id'   => $this->booking_id,
            'venue_id'     => $this->venue_id,
            'rating'       => $this->rating,
            'comment'      => $this->comment,
            'is_published' => $this->is_published,
            'user'         => new UserResource($this->whenLoaded('user')),
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
