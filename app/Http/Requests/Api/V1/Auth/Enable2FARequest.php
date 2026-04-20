<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Enable2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'totp_code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'totp_code.required' => __('validation.required', ['attribute' => __('validation.attributes.totp_code')]),
            'totp_code.digits'   => __('validation.digits', ['attribute' => __('validation.attributes.totp_code'), 'digits' => 6]),
        ];
    }
}
