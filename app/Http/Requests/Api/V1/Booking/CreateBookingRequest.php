<?php

namespace App\Http\Requests\Api\V1\Booking;

use App\Enums\PaymentProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'venue_id'        => ['required', 'integer', 'exists:venues,id'],
            'booking_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'      => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:240', 'multiple_of:30'],
            'payment_mode'    => ['required', 'string', Rule::in(['full', 'deposit'])],
            'payment_provider' => ['required', 'string', Rule::enum(PaymentProvider::class)],
            'deposit_amount'  => [
                'required_if:payment_mode,deposit',
                'nullable',
                'integer',
                'min:1',
                'lte:venue_price',
            ],
            'notes'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'venue_id.required'          => __('validation.required', ['attribute' => __('validation.attributes.venue_id')]),
            'venue_id.exists'            => __('validation.venue_not_found'),
            'booking_date.required'      => __('validation.required', ['attribute' => __('validation.attributes.booking_date')]),
            'booking_date.after_or_equal' => __('validation.booking_date_past'),
            'start_time.required'        => __('validation.required', ['attribute' => __('validation.attributes.start_time')]),
            'duration_minutes.required'  => __('validation.required', ['attribute' => __('validation.attributes.duration_minutes')]),
            'duration_minutes.multiple_of' => __('validation.duration_multiple_of_30'),
            'payment_mode.required'      => __('validation.required', ['attribute' => __('validation.attributes.payment_mode')]),
            'payment_mode.in'            => __('validation.payment_mode_invalid'),
            'payment_provider.required'  => __('validation.required', ['attribute' => __('validation.attributes.payment_provider')]),
            'payment_provider.enum'      => __('validation.payment_provider_invalid'),
            'deposit_amount.required_if' => __('validation.deposit_amount_required'),
            'deposit_amount.lte'         => __('validation.deposit_exceeds_price'),
        ];
    }
}
