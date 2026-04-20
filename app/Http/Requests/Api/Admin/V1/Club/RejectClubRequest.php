<?php

namespace App\Http\Requests\Api\Admin\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class RejectClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => __('validation.required', ['attribute' => __('validation.attributes.reason')]),
        ];
    }
}
