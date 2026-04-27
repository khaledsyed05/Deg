<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePhoneChangeRequest extends FormRequest
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
            'new_phone_number' => ['required', 'string', 'regex:/^\+963[0-9]{9}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_phone_number.regex' => 'صيغة رقم الموبايل غير صحيحة (يجب أن يبدأ بـ +963)',
        ];
    }
}
