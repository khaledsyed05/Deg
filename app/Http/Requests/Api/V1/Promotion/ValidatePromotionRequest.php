<?php

namespace App\Http\Requests\Api\V1\Promotion;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'booking_amount' => ['required', 'integer', 'min:1'],
            'booking_date' => ['nullable', 'date'],
        ];
    }
}
