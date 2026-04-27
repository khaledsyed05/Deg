<?php

namespace App\Http\Requests\Api\V1\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class ShareLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'booking_id' => 'sometimes|nullable|integer|exists:bookings,id',
            'recipient_phones' => 'sometimes|nullable|array|max:10',
            'recipient_phones.*' => 'string|max:20',
            'message_to_recipients' => 'sometimes|nullable|string|max:500',
            'duration_minutes' => 'sometimes|integer|min:15|max:1440',
        ];
    }
}
