<?php

namespace App\Services\Geography;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\StateRepositoryInterface;

class GeographyActivationService
{
    public function __construct(
        private CountryRepositoryInterface $countryRepo,
        private StateRepositoryInterface $stateRepo,
        private CityRepositoryInterface $cityRepo,
    ) {}

    public function activateCountry(Country $country): Country
    {
        return $this->countryRepo->update($country, ['is_active' => true]);
    }

    public function deactivateCountry(Country $country): Country
    {
        return $this->countryRepo->update($country, ['is_active' => false]);
    }

    public function activateState(State $state): State
    {
        return $this->stateRepo->update($state, ['is_active' => true]);
    }

    public function activateCity(City $city): City
    {
        return $this->cityRepo->update($city, ['is_active' => true]);
    }

    public function deactivateCity(City $city): City
    {
        return $this->cityRepo->update($city, ['is_active' => false]);
    }
}
