<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneChangeRequest extends FormRequest
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
            'phone_change_request_id' => 'required|integer|exists:phone_change_requests,id',
            'otp' => 'required|string|digits:6',
        ];
    }
}
