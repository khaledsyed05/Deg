<?php

namespace App\Http\Controllers\Api\V1\Geography;

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
