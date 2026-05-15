<?php

namespace App\Http\Requests\Api\V1\App;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AppStartupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:android,ios'],
            'app_version' => ['required', 'string', 'regex:/^v?\d+\.\d+\.\d+(\.\d+)?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform.in' => 'Platform must be one of: android, ios',
            'app_version.regex' => 'app_version must be semver (e.g. 1.2.3)',
        ];
    }
}
