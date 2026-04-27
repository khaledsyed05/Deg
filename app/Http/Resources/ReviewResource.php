<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'venue_id' => $this->venue_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'helpful_count' => (int) $this->helpful_count,
            'is_helpful' => $this->isHelpfulFor($request->user()),
            'can_edit' => $this->canBeEditedBy($request->user()),
            'is_published' => $this->is_published,
            'user' => new UserResource($this->whenLoaded('user')),
            'venue' => $this->whenLoaded('venue', fn () => [
                'id' => $this->venue->id,
                'slug' => $this->venue->slug,
                'name' => $this->venue->getTranslations('name'),
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
