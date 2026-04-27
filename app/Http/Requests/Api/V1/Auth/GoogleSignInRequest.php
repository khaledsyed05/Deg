<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleSignInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
            'device_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
            'platform' => ['sometimes', 'nullable', 'string', 'in:ios,android'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => __('validation.required', ['attribute' => __('validation.attributes.id_token')]),
        ];
    }
}
