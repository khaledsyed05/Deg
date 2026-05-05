<?php

namespace App\Http\Requests\Api\V1\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWalletSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enabled = $this->boolean('auto_topup_enabled');

        return [
            'auto_topup_enabled' => ['sometimes', 'boolean'],
            'auto_topup_threshold' => [
                $enabled ? 'required' : 'sometimes',
                'nullable',
                'integer',
                'min:1000',
            ],
            'auto_topup_amount' => [
                $enabled ? 'required' : 'sometimes',
                'nullable',
                'integer',
                'min:5000',
            ],
            'auto_topup_payment_method' => [
                $enabled ? 'required' : 'sometimes',
                'nullable',
                'string',
                Rule::in(['syriatel_cash', 'mtn_cash', 'bank_transfer']),
            ],
            'low_balance_alert' => ['sometimes', 'boolean'],
        ];
    }
}
