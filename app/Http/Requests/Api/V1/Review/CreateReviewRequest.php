<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'pros' => ['nullable', 'array', 'max:5'],
            'pros.*' => ['string', 'max:100'],
            'cons' => ['nullable', 'array', 'max:5'],
            'cons.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => __('validation.required', ['attribute' => __('validation.attributes.booking_id')]),
            'booking_id.exists' => __('validation.booking_not_found'),
            'rating.required' => __('validation.required', ['attribute' => __('validation.attributes.rating')]),
            'rating.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.rating'), 'min' => 1]),
            'rating.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.rating'), 'max' => 5]),
        ];
    }
}
