<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class SplitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'split_method' => 'required|in:equal,custom',
            'splits' => 'required|array|min:2|max:20',
            'splits.*.user_id' => 'required|integer|exists:users,id',
            'splits.*.amount' => 'required|numeric|min:0',
        ];
    }
}
