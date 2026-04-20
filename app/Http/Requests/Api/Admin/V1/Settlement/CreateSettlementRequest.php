<?php

namespace App\Http\Requests\Api\Admin\V1\Settlement;

use Illuminate\Foundation\Http\FormRequest;

class CreateSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'club_id'    => ['required', 'integer', 'exists:clubs,id'],
            'from_date'  => ['required', 'date_format:Y-m-d'],
            'to_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'note'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'club_id.required'   => __('validation.required', ['attribute' => __('validation.attributes.club_id')]),
            'club_id.exists'     => __('validation.club_not_found'),
            'from_date.required' => __('validation.required', ['attribute' => __('validation.attributes.from_date')]),
            'to_date.required'   => __('validation.required', ['attribute' => __('validation.attributes.to_date')]),
            'to_date.after_or_equal' => __('validation.to_date_before_from'),
        ];
    }
}
