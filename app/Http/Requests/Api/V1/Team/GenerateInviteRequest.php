<?php

namespace App\Http\Requests\Api\V1\Team;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'max_uses' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
