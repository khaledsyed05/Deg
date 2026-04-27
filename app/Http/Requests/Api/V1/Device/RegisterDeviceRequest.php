<?php

namespace App\Http\Requests\Api\V1\Device;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:255'],
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', 'in:ios,android'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'os_version' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
