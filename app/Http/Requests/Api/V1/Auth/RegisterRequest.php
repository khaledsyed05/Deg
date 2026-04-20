<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', new Phone('SY'), 'unique:users,phone_number'],
            'email'        => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'fcm_token'    => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'phone_number.required'      => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone_number.phone'         => __('validation.phone'),
            'phone_number.unique'        => __('validation.phone_taken'),
            'email.email'        => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.unique'       => __('validation.email_taken'),
            'password.required'  => __('validation.required', ['attribute' => __('validation.attributes.password')]),
            'password.min'       => __('validation.min.string', ['attribute' => __('validation.attributes.password'), 'min' => 8]),
            'password.confirmed' => __('validation.confirmed', ['attribute' => __('validation.attributes.password')]),
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->phone_number) {
            $this->merge([
                'phone_number' => preg_replace('/\s+/', '', $this->phone_number),
            ]);
        }
    }
}
