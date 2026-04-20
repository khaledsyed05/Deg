<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\Geography\ActivateCityRequest;
use App\Models\City;
use App\Models\Settlement;
use App\Services\Geography\GeographyActivationService;
use Illuminate\Http\JsonResponse;

class GeographyController extends Controller
{
    public function __construct(
        private GeographyActivationService $geoService,
    ) {}

    /**
     * Activate or deactivate a city
     *
     * Toggles a city's active status, controlling venue availability in that city. Admin only.
     *
     * @bodyParam is_active boolean required Set to true to activate, false to deactivate. Example: true
     *
     * @response 200 {"success": true, "data": {"city_id": 1, "is_active": true}}
     * @response 403 {"message": "Forbidden"}
     */
    public function activateCity(ActivateCityRequest $request, City $city): JsonResponse
    {
        $this->authorize('create', Settlement::class); // reuse admin gate

        if ($request->is_active) {
            $this->geoService->activateCity($city);
        } else {
            $this->geoService->deactivateCity($city);
        }

        return response()->json([
            'success' => true,
            'data' => ['city_id' => $city->id, 'is_active' => $request->is_active],
        ]);
    }
}
