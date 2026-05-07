<?php

namespace Database\Factories;

use App\Models\VenueCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VenueCategory>
 */
class VenueCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $en = fake()->unique()->word();

        return [
            'slug' => Str::slug($en).'-'.fake()->unique()->numerify('#####'),
            'name' => ['ar' => 'فئة '.$en, 'en' => ucfirst($en)],
            'is_active' => true,
        ];
    }
}
