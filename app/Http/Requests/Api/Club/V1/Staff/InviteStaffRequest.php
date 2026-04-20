<?php

namespace App\Http\Requests\Api\Club\V1\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class InviteStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('club_manager');
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new Phone('SY')],
            'role'  => ['required', 'string', 'in:staff,supervisor'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'phone.required' => __('validation.required', ['attribute' => __('validation.attributes.phone')]),
            'phone.phone'    => __('validation.phone'),
            'role.required'  => __('validation.required', ['attribute' => __('validation.attributes.role')]),
            'role.in'        => __('validation.role_invalid'),
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
