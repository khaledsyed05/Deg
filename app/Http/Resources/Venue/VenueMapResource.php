<?php

namespace App\Http\Resources\Venue;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for map views.
 *
 * Excludes media, reviews, pricing tiers, schedules — anything that
 * would slow the wire-up for a hundred-marker viewport. The full venue
 * resource is fetched separately when the user taps a marker.
 *
 * @mixin Venue
 */
class VenueMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category_id' => $this->category_id !== null ? (int) $this->category_id : null,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'club' => $this->whenLoaded('club', fn () => [
                'id' => $this->club?->id,
                'name' => $this->club?->name,
                'city' => $this->club?->city ? [
                    'id' => $this->club->city->id,
                    'name' => $this->club->city->name,
                    'name_ar' => $this->club->city->name_ar,
                ] : null,
            ]),
            'price_from' => $this->price_from !== null ? (int) $this->price_from : null,
            'rating' => $this->avg_rating !== null ? (float) $this->avg_rating : null,
            'is_active' => $this->status === 'active',
        ];
    }
}
