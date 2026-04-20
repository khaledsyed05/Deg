<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'     => ['sometimes', 'required', new Phone('SY'), Rule::unique('users', 'phone')->ignore($userId)],
            'avatar'    => ['sometimes', 'nullable', 'image', 'max:5120'],
            'city_id'   => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'state_id'  => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.phone'   => __('validation.phone'),
            'phone.unique'  => __('validation.phone_taken'),
            'email.unique'  => __('validation.email_taken'),
            'avatar.image'  => __('validation.image', ['attribute' => __('validation.attributes.avatar')]),
            'avatar.max'    => __('validation.max.file', ['attribute' => __('validation.attributes.avatar'), 'max' => '5MB']),
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
