<?php

namespace App\Http\Requests\Api\V1\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class PayBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:128'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
