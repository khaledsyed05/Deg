<?php

namespace App\Http\Requests\Api\V1\Subscription;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_time' => ['sometimes', 'date_format:H:i'],
            'duration_hours' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'day_of_week' => ['sometimes', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'day_of_month' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after:today'],
            'payment_method_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_methods,id'],
            'auto_pay' => ['sometimes', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422),
        );
    }
}
