<?php

namespace App\Http\Requests\Api\V1\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'pros' => ['sometimes', 'nullable', 'array', 'max:5'],
            'pros.*' => ['string', 'max:100'],
            'cons' => ['sometimes', 'nullable', 'array', 'max:5'],
            'cons.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.rating'), 'min' => 1]),
            'rating.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.rating'), 'max' => 5]),
        ];
    }
}
