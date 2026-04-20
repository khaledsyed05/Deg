<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class LoginOtpSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new Phone('SY')],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.phone'    => __('validation.phone'),
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
