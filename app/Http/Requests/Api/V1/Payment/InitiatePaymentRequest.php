<?php

namespace App\Http\Requests\Api\V1\Payment;

use App\Enums\PaymentProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'booking_id'       => ['required', 'integer', 'exists:bookings,id'],
            'payment_provider' => ['required', 'string', Rule::enum(PaymentProvider::class)],
            'phone_number'     => [
                'required_if:payment_provider,syriatel_cash',
                'required_if:payment_provider,mtn_cash',
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required'        => __('validation.required', ['attribute' => __('validation.attributes.booking_id')]),
            'booking_id.exists'          => __('validation.booking_not_found'),
            'payment_provider.required'  => __('validation.required', ['attribute' => __('validation.attributes.payment_provider')]),
            'payment_provider.enum'      => __('validation.payment_provider_invalid'),
            'phone_number.required_if'   => __('validation.phone_number_required_for_provider'),
        ];
    }
}
