<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class LoginOtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'     => ['required', 'string', new Phone('SY')],
            'otp'       => ['required', 'string', 'digits:6'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.phone'    => __('validation.phone'),
            'otp.required'   => __('validation.required', ['attribute' => __('validation.attributes.otp')]),
            'otp.digits'     => __('validation.digits', ['attribute' => __('validation.attributes.otp'), 'digits' => 6]),
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->phone) {
            $this->merge([
                'phone' => preg_replace('/\s+/', '', $this->phone),
            ]);
        }
    }
}
