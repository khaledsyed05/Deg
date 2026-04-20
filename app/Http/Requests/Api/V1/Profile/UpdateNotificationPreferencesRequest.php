<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'booking_confirmed'  => ['sometimes', 'boolean'],
            'booking_reminder'   => ['sometimes', 'boolean'],
            'booking_cancelled'  => ['sometimes', 'boolean'],
            'promotions'         => ['sometimes', 'boolean'],
            'waitlist_available' => ['sometimes', 'boolean'],
        ];
    }
}
