<?php

namespace App\Http\Requests\Api\Admin\V1\Geography;

use Illuminate\Foundation\Http\FormRequest;

class ActivateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => __('validation.required', ['attribute' => __('validation.attributes.is_active')]),
        ];
    }
}
