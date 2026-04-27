<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\StateRepositoryInterface;
use App\Services\Geography\GeographyActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Yajra\DataTables\Facades\DataTables;

class GeographyController extends Controller
{
    public function __construct(
        private CountryRepositoryInterface $countryRepo,
        private StateRepositoryInterface $stateRepo,
        private CityRepositoryInterface $cityRepo,
        private GeographyActivationService $geoService,
    ) {}

    // ---------------- Countries ----------------

    public function countries(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $isActive = $request->input('is_active');

        $countries = $this->countryRepo->query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('iso2', 'like', "%{$search}%");
            }))
            ->when($isActive !== null && $isActive !== '', fn ($q) => $q->where('is_active', (bool) $isActive))
            ->withCount('states')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Geography/Countries/Index', [
            'countries' => $countries,
            'filters' => ['search' => $search, 'is_active' => $isActive],
        ]);
    }

    public function createCountry(): Response
    {
        return Inertia::render('Admin/Geography/Countries/Form', [
            'country' => null,
        ]);
    }

    public function storeCountry(Request $request): RedirectResponse
    {
        $data = $this->validateCountry($request);

        $this->countryRepo->create($data);

        return redirect()->route('admin.geography.countries')
            ->with('flash_key', 'geoCountryCreated')
            ->with('flash_type', 'success');
    }

    public function editCountry(Country $country): Response
    {
        return Inertia::render('Admin/Geography/Countries/Form', [
            'country' => $country,
        ]);
    }

    public function updateCountry(Request $request, Country $country): RedirectResponse
    {
        $data = $this->validateCountry($request, $country->id);

        $this->countryRepo->update($country, $data);

        return redirect()->route('admin.geography.countries')
            ->with('flash_key', 'geoCountryUpdated')
            ->with('flash_type', 'success');
    }

    public function destroyCountry(Country $country): RedirectResponse
    {
        if ($country->states()->exists()) {
            return back()
                ->with('flash_key', 'geoCountryHasStates')
                ->with('flash_type', 'error');
        }

        $this->countryRepo->delete($country);

        return redirect()->route('admin.geography.countries')
            ->with('flash_key', 'geoCountryDeleted')
            ->with('flash_type', 'success');
    }

    // ---------------- States ----------------

    public function states(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $countryId = $request->integer('country_id');
        $isActive = $request->input('is_active');

        $states = $this->stateRepo->query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            }))
            ->when($countryId > 0, fn ($q) => $q->where('country_id', $countryId))
            ->when($isActive !== null && $isActive !== '', fn ($q) => $q->where('is_active', (bool) $isActive))
            ->with('country:id,name,name_ar')
            ->withCount('cities')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Geography/States/Index', [
            'states' => $states,
            'countries' => $this->countryOptions(),
            'filters' => [
                'search' => $search,
                'country_id' => $countryId > 0 ? $countryId : null,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function createState(): Response
    {
        return Inertia::render('Admin/Geography/States/Form', [
            'state' => null,
            'countries' => $this->countryOptions(),
        ]);
    }

    public function storeState(Request $request): RedirectResponse
    {
        $data = $this->validateState($request);

        $this->stateRepo->create($data);

        return redirect()->route('admin.geography.states')
            ->with('flash_key', 'geoStateCreated')
            ->with('flash_type', 'success');
    }

    public function editState(State $state): Response
    {
        return Inertia::render('Admin/Geography/States/Form', [
            'state' => $state,
            'countries' => $this->countryOptions(),
        ]);
    }

    public function updateState(Request $request, State $state): RedirectResponse
    {
        $data = $this->validateState($request);

        $this->stateRepo->update($state, $data);

        return redirect()->route('admin.geography.states')
            ->with('flash_key', 'geoStateUpdated')
            ->with('flash_type', 'success');
    }

    public function destroyState(State $state): RedirectResponse
    {
        if ($state->cities()->exists()) {
            return back()
                ->with('flash_key', 'geoStateHasCities')
                ->with('flash_type', 'error');
        }

        $this->stateRepo->delete($state);

        return redirect()->route('admin.geography.states')
            ->with('flash_key', 'geoStateDeleted')
            ->with('flash_type', 'success');
    }

    // ---------------- Cities ----------------

    public function cities(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $stateId = $request->integer('state_id');
        $countryId = $request->integer('country_id');
        $isActive = $request->input('is_active');

        $cities = $this->cityRepo->query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            }))
            ->when($stateId > 0, fn ($q) => $q->where('state_id', $stateId))
            ->when($countryId > 0, fn ($q) => $q->whereHas('state', fn ($q) => $q->where('country_id', $countryId)))
            ->when($isActive !== null && $isActive !== '', fn ($q) => $q->where('is_active', (bool) $isActive))
            ->with('state:id,name,name_ar,country_id', 'state.country:id,name,name_ar')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Geography/Cities/Index', [
            'cities' => $cities,
            'countries' => $this->countryOptions(),
            'states' => $this->stateOptions($countryId > 0 ? $countryId : null),
            'filters' => [
                'search' => $search,
                'country_id' => $countryId > 0 ? $countryId : null,
                'state_id' => $stateId > 0 ? $stateId : null,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function createCity(): Response
    {
        return Inertia::render('Admin/Geography/Cities/Form', [
            'city' => null,
            'countries' => $this->countryOptions(),
            'states' => $this->stateOptions(),
        ]);
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $data = $this->validateCity($request);

        $this->cityRepo->create($data);

        return redirect()->route('admin.geography.cities')
            ->with('flash_key', 'geoCityCreated')
            ->with('flash_type', 'success');
    }

    public function editCity(City $city): Response
    {
        return Inertia::render('Admin/Geography/Cities/Form', [
            'city' => $city->load('state:id,country_id'),
            'countries' => $this->countryOptions(),
            'states' => $this->stateOptions(),
        ]);
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $data = $this->validateCity($request);

        $this->cityRepo->update($city, $data);

        return redirect()->route('admin.geography.cities')
            ->with('flash_key', 'geoCityUpdated')
            ->with('flash_type', 'success');
    }

    public function destroyCity(City $city): RedirectResponse
    {
        if ($city->clubs()->exists()) {
            return back()
                ->with('flash_key', 'geoCityHasClubs')
                ->with('flash_type', 'error');
        }

        $this->cityRepo->delete($city);

        return redirect()->route('admin.geography.cities')
            ->with('flash_key', 'geoCityDeleted')
            ->with('flash_type', 'success');
    }

    public function citiesDatatables(): JsonResponse
    {
        $query = $this->cityRepo->query()
            ->select(['id', 'name', 'name_ar', 'is_active']);

        return DataTables::of($query)
            ->addColumn('actions', '')
            ->make(true);
    }

    public function toggleCity(Request $request, City $city): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        if ($request->boolean('is_active')) {
            $this->geoService->activateCity($city);
        } else {
            $this->geoService->deactivateCity($city);
        }

        return back()
            ->with('flash_key', 'geoCityToggled')
            ->with('flash_type', 'success');
    }

    // ---------------- helpers ----------------

    /** @return array<string, mixed> */
    private function validateCountry(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'iso2' => ['required', 'string', 'size:2', 'unique:countries,iso2'.($ignoreId ? ",{$ignoreId}" : '')],
            'iso3' => ['required', 'string', 'size:3', 'unique:countries,iso3'.($ignoreId ? ",{$ignoreId}" : '')],
            'phone_code' => ['required', 'string', 'max:10'],
            'capital' => ['nullable', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateState(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'state_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateCity(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<int, array{id:int,name:string,name_ar:?string}> */
    private function countryOptions(): array
    {
        return $this->countryRepo->query()
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'name_ar' => $c->name_ar])
            ->all();
    }

    /** @return array<int, array{id:int,country_id:int,name:string,name_ar:?string}> */
    private function stateOptions(?int $countryId = null): array
    {
        return $this->stateRepo->query()
            ->when($countryId !== null, fn ($q) => $q->where('country_id', $countryId))
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'name_ar'])
            ->map(fn ($s) => ['id' => $s->id, 'country_id' => $s->country_id, 'name' => $s->name, 'name_ar' => $s->name_ar])
            ->all();
    }
}
