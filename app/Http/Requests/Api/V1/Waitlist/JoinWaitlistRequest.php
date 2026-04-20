<?php

namespace App\Http\Requests\Api\V1\Waitlist;

use Illuminate\Foundation\Http\FormRequest;

class JoinWaitlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'venue_id'         => ['required', 'integer', 'exists:venues,id'],
            'preferred_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_time'   => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:240', 'multiple_of:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'venue_id.required'         => __('validation.required', ['attribute' => __('validation.attributes.venue_id')]),
            'venue_id.exists'           => __('validation.venue_not_found'),
            'preferred_date.required'   => __('validation.required', ['attribute' => __('validation.attributes.preferred_date')]),
            'preferred_date.after_or_equal' => __('validation.date_in_past'),
        ];
    }
}
