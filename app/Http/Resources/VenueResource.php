<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'slug' => $this->category?->slug,
                'name' => $this->category?->name,
            ]),
            'club' => [
                'id' => $this->club?->id,
                'slug' => $this->club?->slug,
                'name' => $this->club?->name,
                'city' => $this->club?->city ? [
                    'id' => $this->club->city->id,
                    'name' => $this->club->city->name,
                    'name_ar' => $this->club->city->name_ar,
                ] : null,
            ],
            'location' => [
                'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            ],
            'main_image_url' => $this->getFirstMediaUrl('images') ?: null,
            'pricing' => [
                'price_from' => $this->price_from,
                'currency' => 'SYP',
            ],
            'rating' => $this->avg_rating !== null ? (float) $this->avg_rating : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'is_favorite' => $this->isFavoritedBy($user),
            'is_featured' => (bool) $this->is_featured,
            'is_open_now' => $this->isOpenNow(),
            'view_count' => (int) ($this->view_count ?? 0),
            'distance_km' => isset($this->distance_km) ? round((float) $this->distance_km, 2) : null,
            'status' => $this->status,
        ];
    }
}
