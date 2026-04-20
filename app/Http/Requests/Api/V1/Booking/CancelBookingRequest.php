<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'boolean', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmed.required' => __('validation.required', ['attribute' => __('validation.attributes.confirmed')]),
            'confirmed.accepted' => __('validation.cancellation_confirmation_required'),
        ];
    }
}
