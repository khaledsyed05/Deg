<?php

namespace App\Http\Requests\Api\V1\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'otp' => ['required', 'string', 'regex:/^\d{4,8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.regex' => 'رمز التحقق يجب أن يكون من 4 إلى 8 أرقام',
        ];
    }
}
