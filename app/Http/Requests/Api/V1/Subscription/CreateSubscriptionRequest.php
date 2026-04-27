<?php

namespace App\Http\Requests\Api\V1\Subscription;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSubscriptionRequest extends FormRequest
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
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'frequency' => ['required', 'in:weekly,biweekly,monthly,custom'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:90', 'required_if:frequency,custom'],
            'day_of_week' => [
                'nullable',
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'required_if:frequency,weekly',
                'required_if:frequency,biweekly',
            ],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31', 'required_if:frequency,monthly'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
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
