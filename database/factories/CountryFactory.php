<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'iso2'       => strtoupper(fake()->unique()->lexify('??')),
            'iso3'       => strtoupper(fake()->unique()->lexify('???')),
            'name'       => fake()->country(),
            'phone_code' => '+' . fake()->numerify('###'),
        ];
    }
}
