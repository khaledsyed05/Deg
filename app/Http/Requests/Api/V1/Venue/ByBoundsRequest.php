<?php

namespace App\Http\Requests\Api\V1\Venue;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the bounding-box query for {@see VenueController@byBounds}.
 *
 * Accepts two interchangeable param styles:
 *   1. north/south/east/west (Sprint 6 prompt suggestion)
 *   2. ne_lat/ne_lng/sw_lat/sw_lng (BACKEND_REQUIREMENTS.md spec)
 *
 * `prepareForValidation` normalises both styles to north/south/east/west
 * so downstream code only deals with one shape.
 */
class ByBoundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('north') && $this->has('ne_lat')) {
            $merge['north'] = $this->input('ne_lat');
        }
        if (! $this->has('east') && $this->has('ne_lng')) {
            $merge['east'] = $this->input('ne_lng');
        }
        if (! $this->has('south') && $this->has('sw_lat')) {
            $merge['south'] = $this->input('sw_lat');
        }
        if (! $this->has('west') && $this->has('sw_lng')) {
            $merge['west'] = $this->input('sw_lng');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'north' => ['required', 'numeric', 'between:-90,90'],
            'south' => ['required', 'numeric', 'between:-90,90'],
            'east' => ['required', 'numeric', 'between:-180,180'],
            'west' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:venue_categories,id'],
            'sport_id' => ['sometimes', 'nullable', 'integer', 'exists:venue_categories,id'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $v->errors()->isEmpty()) {
                return;
            }

            $north = (float) $this->input('north');
            $south = (float) $this->input('south');
            $east = (float) $this->input('east');
            $west = (float) $this->input('west');

            if ($north <= $south) {
                $v->errors()->add('north', 'north must be greater than south');
            }

            // Antimeridian wrap-around is not supported (see Venue::scopeWithinBounds).
            if ($east <= $west) {
                $v->errors()->add('east', 'east must be greater than west');
            }
        });
    }
}
