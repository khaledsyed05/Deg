<?php

namespace App\Http\Requests\Api\V1\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = (int) config('wallet.min_topup', 5_000);
        $max = (int) config('wallet.max_topup_per_transaction', 1_000_000);

        return [
            'amount' => ['required', 'integer', "min:{$min}", "max:{$max}"],
            'method' => ['required', Rule::in(['syriatel', 'mtn', 'bank'])],
            'phone' => ['required_if:method,syriatel,mtn', 'nullable', 'string', 'regex:/^\+963\d{9}$/'],
        ];
    }

    public function messages(): array
    {
        $min = (int) config('wallet.min_topup', 5_000);
        $max = (int) config('wallet.max_topup_per_transaction', 1_000_000);

        return [
            'amount.min' => "الحد الأدنى للشحن {$min} ل.س",
            'amount.max' => "الحد الأقصى للشحن لكل عملية {$max} ل.س",
            'method.in' => 'طريقة الدفع غير مدعومة',
            'phone.required_if' => 'رقم الهاتف مطلوب لطرق الدفع الجوّالة',
            'phone.regex' => 'رقم الهاتف غير صحيح (يجب أن يبدأ بـ +963 ويتبعه 9 أرقام)',
        ];
    }
}
