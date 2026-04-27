<?php

namespace App\Http\Requests\Api\V1\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class ReportEmergencyRequest extends FormRequest
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
            'type' => 'required|in:medical,safety,security,fire,other',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'description' => 'required|string|min:10|max:2000',
            'booking_id' => 'sometimes|nullable|integer|exists:bookings,id',
            'contact_phone' => 'sometimes|nullable|string|max:20',
            'location' => 'sometimes|nullable|array',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'location.address' => 'sometimes|nullable|string|max:500',
            'attachments' => 'sometimes|nullable|array|max:5',
            'attachments.*' => 'string',
        ];
    }
}
