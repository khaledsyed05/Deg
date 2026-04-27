<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'in:ar,en'],
            'timezone' => ['sometimes', 'timezone'],
            'privacy' => ['sometimes', 'array'],
            'privacy.profile_visibility' => ['sometimes', 'in:public,friends,private'],
            'privacy.show_phone_number' => ['sometimes', 'boolean'],
            'privacy.show_bookings' => ['sometimes', 'boolean'],
            'privacy.show_reviews' => ['sometimes', 'boolean'],
            'privacy.allow_marketing' => ['sometimes', 'boolean'],
            'privacy.data_sharing' => ['sometimes', 'boolean'],
            'preferences' => ['sometimes', 'array'],
            'preferences.currency' => ['sometimes', 'string', 'max:8'],
            'preferences.distance_unit' => ['sometimes', 'in:km,mi'],
            'preferences.time_format' => ['sometimes', 'in:12h,24h'],
            'preferences.push_sound' => ['sometimes', 'boolean'],
            'preferences.push_vibrate' => ['sometimes', 'boolean'],
        ];
    }
}
