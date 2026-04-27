<?php

namespace App\Services\Geography;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class GeographyService
{
    private int $cacheTtl = 86400;

    public function getCountries(): array
    {
        return Cache::remember('geography_countries', $this->cacheTtl, function () {
            return Country::visible()->get()->toArray();
        });
    }

    public function getStatesByCountry(string $iso2): array
    {
        return Cache::remember("geography_states_{$iso2}", $this->cacheTtl, function () use ($iso2) {
            $country = Country::visible()->where('iso2', strtoupper($iso2))->first();
            if (! $country) {
                return [];
            }

            return $country->states()->visible()->orderBy('display_order')->orderBy('name')->get()->toArray();
        });
    }

    public function getCitiesByState(int $stateId): array
    {
        return Cache::remember("geography_cities_state_{$stateId}", $this->cacheTtl, function () use ($stateId) {
            return City::visible()
                ->where('state_id', $stateId)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    public function getPopularCities(int $limit = 10): array
    {
        return Cache::remember("geography_popular_cities_{$limit}", $this->cacheTtl, function () use ($limit) {
            return City::visible()
                ->popular()
                ->orderBy('display_order')
                ->orderByDesc('venues_count')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getCityDetails(int $cityId): ?City
    {
        return Cache::remember("geography_city_{$cityId}", $this->cacheTtl, function () use ($cityId) {
            return City::visible()->with(['country', 'state'])->find($cityId);
        });
    }

    public function detectNearestCity(float $latitude, float $longitude): ?array
    {
        $cities = City::visible()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['country', 'state'])
            ->get();

        $city = $cities
            ->map(function ($c) use ($latitude, $longitude) {
                $c->distance_km = $c->distanceFrom($latitude, $longitude);

                return $c;
            })
            ->sortBy('distance_km')
            ->first();

        if (! $city) {
            return null;
        }

        return [
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
                'name_ar' => $city->name_ar,
            ],
            'state' => $city->state ? [
                'id' => $city->state->id,
                'name' => $city->state->name,
                'name_ar' => $city->state->name_ar,
            ] : null,
            'country' => $city->country ? [
                'iso2' => $city->country->iso2,
                'name' => $city->country->name,
                'name_ar' => $city->country->name_ar,
            ] : null,
            'distance_km' => round((float) $city->distance_km, 2),
        ];
    }

    public function getVenueClusters(float $lat, float $lng, int $radiusKm = 50, int $zoom = 12): array
    {
        $cacheKey = "venue_clusters_{$lat}_{$lng}_{$radiusKm}_{$zoom}";

        return Cache::remember($cacheKey, 300, function () use ($lat, $lng, $radiusKm) {
            $cities = City::visible()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('venues_count', '>', 0)
                ->get()
                ->map(function ($c) use ($lat, $lng) {
                    $c->distance_km = $c->distanceFrom($lat, $lng);

                    return $c;
                })
                ->filter(fn ($c) => $c->distance_km <= $radiusKm)
                ->sortBy('distance_km')
                ->values();

            return [
                'clusters' => $cities->map(fn ($c) => [
                    'lat' => (float) $c->latitude,
                    'lng' => (float) $c->longitude,
                    'count' => (int) $c->venues_count,
                    'city' => $c->name_ar ?: $c->name,
                    'distance_km' => round((float) $c->distance_km, 2),
                ])->toArray(),
                'total_venues' => (int) $cities->sum('venues_count'),
            ];
        });
    }
}
