<?php

namespace App\Http\Requests\Api\Club\V1\Venue;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVenueRequest extends FormRequest
{
    /** @var array<string, mixed>|null */
    private ?array $parsedOpeningHours = null;

    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('club_manager');
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'array'],
            'name.ar'                 => ['required', 'string', 'max:255'],
            'name.en'                 => ['nullable', 'string', 'max:255'],
            'description'             => ['nullable', 'array'],
            'description.ar'          => ['nullable', 'string', 'max:5000'],
            'description.en'          => ['nullable', 'string', 'max:5000'],
            'city_id'                 => ['required', 'integer', 'exists:cities,id'],
            'address'                 => ['required', 'string', 'max:500'],
            'latitude'                => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'               => ['nullable', 'numeric', 'between:-180,180'],
            'category_id'             => ['required', 'integer', 'exists:venue_categories,id'],
            'sport_ids'               => ['required', 'array', 'min:1'],
            'sport_ids.*'             => ['integer', 'exists:sport_categories,id'],
            'images'                  => ['nullable', 'array', 'max:10'],
            'images.*'                => ['image', 'max:5120'],
            'opening_hours'           => ['required', 'array'],
            'opening_hours.*.day'     => ['required', Rule::enum(DayOfWeek::class)],
            'opening_hours.*.open'    => ['required', 'date_format:H:i'],
            'opening_hours.*.close'   => ['required', 'date_format:H:i', 'after:opening_hours.*.open'],
            'opening_hours.*.closed'  => ['sometimes', 'boolean'],
            'price_per_hour'          => ['required', 'integer', 'min:1'],
            'deposit_percentage'      => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.ar.required'           => __('validation.required', ['attribute' => __('validation.attributes.name_ar')]),
            'city_id.required'           => __('validation.required', ['attribute' => __('validation.attributes.city_id')]),
            'city_id.exists'             => __('validation.city_not_found'),
            'address.required'           => __('validation.required', ['attribute' => __('validation.attributes.address')]),
            'category_id.required'       => __('validation.required', ['attribute' => __('validation.attributes.category_id')]),
            'sport_ids.required'         => __('validation.required', ['attribute' => __('validation.attributes.sport_ids')]),
            'opening_hours.required'     => __('validation.required', ['attribute' => __('validation.attributes.opening_hours')]),
            'opening_hours.*.close.after' => __('validation.closing_before_opening'),
            'price_per_hour.required'    => __('validation.required', ['attribute' => __('validation.attributes.price_per_hour')]),
        ];
    }

    public function passedValidation(): void
    {
        if ($this->has('opening_hours')) {
            $this->parsedOpeningHours = collect($this->opening_hours)
                ->keyBy('day')
                ->map(fn (array $slot) => [
                    'open'   => $slot['open'],
                    'close'  => $slot['close'],
                    'closed' => $slot['closed'] ?? false,
                ])
                ->all();

            $this->merge(['parsed_opening_hours' => $this->parsedOpeningHours]);
        }
    }
}
