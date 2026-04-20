<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'address'             => $this->address,
            'latitude'            => $this->latitude,
            'longitude'           => $this->longitude,
            'city_id'             => $this->city_id,
            'category_id'         => $this->category_id,
            'status'              => $this->status,
            'avg_rating'          => $this->avg_rating,
            'reviews_count'       => $this->reviews_count,
            'price_per_hour'      => $this->price_per_hour,
            'deposit_percentage'  => $this->deposit_percentage,
            'opening_hours'       => $this->opening_hours,
            'images'              => $this->getMedia('images')->map(fn ($m) => $m->getUrl()),
            'club'                => new ClubResource($this->whenLoaded('club')),
        ];
    }
}
