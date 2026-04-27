<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_slot_date' => ['required', 'date', 'after:today'],
            'new_start_time' => ['required', 'date_format:H:i'],
            'new_end_time' => ['required', 'date_format:H:i', 'after:new_start_time'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
