<?php

namespace App\Http\Controllers\Api\V1\Geography;

use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Geography\GeographyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeographyController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly GeographyService $service) {}

    public function countries(): JsonResponse
    {
        return $this->success($this->service->getCountries());
    }

    public function statesByCountry(string $iso2): JsonResponse
    {
        return $this->success($this->service->getStatesByCountry($iso2));
    }

    public function citiesByState(int $stateId): JsonResponse
    {
        return $this->success($this->service->getCitiesByState($stateId));
    }

    public function show(int $cityId): JsonResponse
    {
        $city = $this->service->getCityDetails($cityId);
        if (! $city) {
            return $this->notFound(__('City not found'));
        }

        return $this->success($city);
    }

    public function popularCities(Request $request): JsonResponse
    {
        $limit = min(50, max(1, $request->integer('limit', 10)));

        return $this->success($this->service->getPopularCities($limit));
    }

    /**
     * Spec endpoint: GET /cities/{id}/neighborhoods.
     *
     * Until a dedicated neighborhood model lands (Sprint 6 Maps), this
     * returns the city's known neighborhoods array if the City model
     * exposes one, otherwise an empty list. The envelope is the same
     * either way so mobile can render gracefully.
     */
    public function neighborhoods(int $cityId): JsonResponse
    {
        $city = $this->service->getCityDetails($cityId);
        if (! $city) {
            return $this->notFound(__('City not found'));
        }

        $neighborhoods = is_array($city['neighborhoods'] ?? null) ? $city['neighborhoods'] : [];

        return $this->success(['city_id' => $cityId, 'neighborhoods' => $neighborhoods]);
    }

    public function detect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->service->detectNearestCity((float) $data['latitude'], (float) $data['longitude']);

        if (! $result) {
            return $this->notFound(__('No city found near these coordinates'));
        }

        return $this->success($result);
    }

    /**
     * Map clusters anchored at city centres, with venue counts per city.
     *
     * Currently returns city-grouped clusters (one cluster per city, with
     * its venues_count and city-centre lat/lng). The `zoom` parameter is
     * accepted but currently ignored — true zoom-aware clustering (grid
     * or geohash-based, with sub-city granularity at high zoom) is
     * deferred until venue density across multiple cities justifies it.
     * For now mobile clusters client-side from {@see VenueController::byBounds}
     * when it needs viewport-precise grouping.
     *
     * Response shape:
     * ```
     * {
     *   "clusters": [{ "lat", "lng", "count", "city", "distance_km" }],
     *   "total_venues": int
     * }
     * ```
     */
    public function venueClusters(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:1', 'max:500'],
            'zoom' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        return $this->success($this->service->getVenueClusters(
            (float) $data['lat'],
            (float) $data['lng'],
            (int) ($data['radius'] ?? 50),
            (int) ($data['zoom'] ?? 12),
        ));
    }
}
