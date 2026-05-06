<?php

namespace Tests\Feature\Geography;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Tests\TestCase;

class GeographyEndpointsTest extends TestCase
{
    private function seedSyriaSample(): array
    {
        $country = Country::create([
            'iso2' => 'SY', 'iso3' => 'SYR', 'name' => 'Syria', 'name_ar' => 'سوريا',
            'phone_code' => '+963', 'currency' => 'SYP', 'currency_symbol' => 'ل.س',
            'flag_emoji' => '🇸🇾', 'is_active' => true, 'is_visible' => true, 'display_order' => 1,
        ]);
        $state = State::create([
            'country_id' => $country->id, 'name' => 'Damascus Governorate', 'name_ar' => 'محافظة دمشق',
            'type' => 'governorate', 'latitude' => 33.5138, 'longitude' => 36.2765,
            'is_active' => true, 'is_visible' => true, 'display_order' => 1,
        ]);
        $city = City::create([
            'country_id' => $country->id, 'state_id' => $state->id,
            'name' => 'Damascus', 'name_ar' => 'دمشق',
            'latitude' => 33.5138, 'longitude' => 36.2765,
            'is_active' => true, 'is_visible' => true, 'is_popular' => true, 'venues_count' => 5,
        ]);

        return [$country, $state, $city];
    }

    public function test_countries_returns_visible_only(): void
    {
        $this->seedSyriaSample();
        Country::create([
            'iso2' => 'XX', 'iso3' => 'XXX', 'name' => 'Hidden', 'phone_code' => '+0',
            'is_active' => false, 'is_visible' => false,
        ]);

        $this->getJson('/api/v1/geography/countries')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.iso2', 'SY');
    }

    public function test_states_by_country_iso(): void
    {
        $this->seedSyriaSample();
        $this->getJson('/api/v1/geography/countries/SY/states')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_ar', 'محافظة دمشق');
    }

    public function test_cities_by_state(): void
    {
        [, $state] = $this->seedSyriaSample();
        $this->getJson("/api/v1/geography/states/{$state->id}/cities")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Damascus');
    }

    public function test_popular_cities(): void
    {
        $this->seedSyriaSample();
        // Sprint 8: deprecated /geography/cities/popular alias removed; use canonical /cities.
        $this->getJson('/api/v1/cities?limit=5')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_city_show(): void
    {
        [,, $city] = $this->seedSyriaSample();
        // Sprint 8: deprecated /geography/cities/{id} alias removed; use canonical /cities/{id}.
        $this->getJson("/api/v1/cities/{$city->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $city->id);
    }

    public function test_city_show_404_for_invisible(): void
    {
        [,, $city] = $this->seedSyriaSample();
        $city->update(['is_visible' => false]);
        $this->getJson("/api/v1/cities/{$city->id}")
            ->assertStatus(404);
    }

    public function test_detect_returns_nearest_city(): void
    {
        $this->seedSyriaSample();
        $this->postJson('/api/v1/geography/detect', [
            'latitude' => 33.5138, 'longitude' => 36.2765,
        ])->assertStatus(200)
            ->assertJsonPath('data.city.name', 'Damascus')
            ->assertJsonPath('data.distance_km', 0);
    }

    public function test_detect_validates_coords(): void
    {
        $this->postJson('/api/v1/geography/detect', [
            'latitude' => 'bad', 'longitude' => 'bad',
        ])->assertStatus(422);
    }

    public function test_venue_clusters(): void
    {
        $this->seedSyriaSample();
        // Sprint 8: deprecated /geography/venues/clusters alias removed; use canonical /venues/clusters.
        $this->getJson('/api/v1/venues/clusters?lat=33.5138&lng=36.2765&radius=50')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['clusters', 'total_venues']])
            ->assertJsonPath('data.total_venues', 5);
    }

    public function test_haversine_distance(): void
    {
        [,, $city] = $this->seedSyriaSample();
        // Distance from Damascus to Aleppo (~ 320km)
        $distance = $city->distanceFrom(36.2021, 37.1343);
        $this->assertGreaterThan(280, $distance);
        $this->assertLessThan(360, $distance);
    }
}
