<?php

namespace App\Http\Requests\Api\Club\V1\Venue;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('club_manager');
    }

    public function rules(): array
    {
        return [
            'name'                    => ['sometimes', 'required', 'array'],
            'name.ar'                 => ['sometimes', 'required', 'string', 'max:255'],
            'name.en'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'             => ['sometimes', 'nullable', 'array'],
            'description.ar'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description.en'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'city_id'                 => ['sometimes', 'required', 'integer', 'exists:cities,id'],
            'address'                 => ['sometimes', 'required', 'string', 'max:500'],
            'latitude'                => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'               => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'category_id'             => ['sometimes', 'required', 'integer', 'exists:venue_categories,id'],
            'sport_ids'               => ['sometimes', 'required', 'array', 'min:1'],
            'sport_ids.*'             => ['integer', 'exists:sport_categories,id'],
            'images'                  => ['sometimes', 'nullable', 'array', 'max:10'],
            'images.*'                => ['image', 'max:5120'],
            'opening_hours'           => ['sometimes', 'required', 'array'],
            'opening_hours.*.day'     => ['required', Rule::enum(DayOfWeek::class)],
            'opening_hours.*.open'    => ['required', 'date_format:H:i'],
            'opening_hours.*.close'   => ['required', 'date_format:H:i', 'after:opening_hours.*.open'],
            'opening_hours.*.closed'  => ['sometimes', 'boolean'],
            'price_per_hour'          => ['sometimes', 'required', 'integer', 'min:1'],
            'deposit_percentage'      => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.ar.required'           => __('validation.required', ['attribute' => __('validation.attributes.name_ar')]),
            'city_id.exists'             => __('validation.city_not_found'),
            'opening_hours.*.close.after' => __('validation.closing_before_opening'),
        ];
    }

    public function passedValidation(): void
    {
        if ($this->has('opening_hours')) {
            $parsedOpeningHours = collect($this->opening_hours)
                ->keyBy('day')
                ->map(fn (array $slot) => [
                    'open'   => $slot['open'],
                    'close'  => $slot['close'],
                    'closed' => $slot['closed'] ?? false,
                ])
                ->all();

            $this->merge(['parsed_opening_hours' => $parsedOpeningHours]);
        }
    }
}
