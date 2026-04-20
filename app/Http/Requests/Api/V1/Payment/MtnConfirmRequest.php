<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MtnConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_id'   => ['required', 'integer', 'exists:payments,id'],
            'otp'          => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => __('validation.required', ['attribute' => __('validation.attributes.payment_id')]),
            'payment_id.exists'   => __('validation.payment_not_found'),
            'otp.required'        => __('validation.required', ['attribute' => __('validation.attributes.otp')]),
            'otp.digits'          => __('validation.digits', ['attribute' => __('validation.attributes.otp'), 'digits' => 6]),
        ];
    }
}
