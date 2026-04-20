<?php

namespace App\Http\Requests\Api\V1\Venue;

use Illuminate\Foundation\Http\FormRequest;

class SearchVenuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query'       => ['required', 'string', 'min:2', 'max:100'],
            'city_id'     => ['nullable', 'integer', 'exists:cities,id'],
            'category_id' => ['nullable', 'integer', 'exists:venue_categories,id'],
            'sport_id'    => ['nullable', 'integer', 'exists:sport_categories,id'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => __('validation.required', ['attribute' => __('validation.attributes.query')]),
            'query.min'      => __('validation.min.string', ['attribute' => __('validation.attributes.query'), 'min' => 2]),
        ];
    }
}
