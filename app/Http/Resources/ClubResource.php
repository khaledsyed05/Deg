<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'status'       => $this->status,
            'avg_rating'   => $this->avg_rating,
            'reviews_count' => $this->reviews_count,
            'logo_url'     => $this->getFirstMediaUrl('logo'),
            'city_id'      => $this->city_id,
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
