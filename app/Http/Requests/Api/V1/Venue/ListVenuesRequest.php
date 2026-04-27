<?php

namespace App\Http\Requests\Api\V1\Venue;

use Illuminate\Foundation\Http\FormRequest;

class ListVenuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'category_id' => ['nullable', 'integer', 'exists:venue_categories,id'],
            'sport_id' => ['nullable', 'integer', 'exists:sport_categories,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0', 'gte:min_price'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:price_asc,price_desc,rating,popular,newest'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
