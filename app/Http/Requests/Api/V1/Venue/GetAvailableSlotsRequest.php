<?php

namespace App\Http\Requests\Api\V1\Venue;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'             => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240', 'multiple_of:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'             => __('validation.required', ['attribute' => __('validation.attributes.date')]),
            'date.after_or_equal'       => __('validation.date_in_past'),
            'duration_minutes.required' => __('validation.required', ['attribute' => __('validation.attributes.duration_minutes')]),
            'duration_minutes.multiple_of' => __('validation.duration_multiple_of_30'),
        ];
    }
}
