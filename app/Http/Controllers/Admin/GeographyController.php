<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Services\Geography\GeographyActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeographyController extends Controller
{
    public function __construct(
        private CityRepositoryInterface $cityRepo,
        private GeographyActivationService $geoService,
    ) {}

    public function cities(): Response
    {
        $cities = $this->cityRepo->query()
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'is_active'])
            ->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->name,
                'name_ar' => $city->name_ar,
                'is_active' => $city->is_active,
            ]);

        return Inertia::render('Admin/Geography/Cities', [
            'cities' => $cities,
        ]);
    }

    public function toggleCity(Request $request, City $city): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        if ($request->boolean('is_active')) {
            $this->geoService->activateCity($city);
        } else {
            $this->geoService->deactivateCity($city);
        }

        return redirect()->route('admin.geography.cities')
            ->with('success', 'تم تحديث حالة المدينة');
    }
}
