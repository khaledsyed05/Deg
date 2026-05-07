<?php

namespace App\Http\Requests\Api\V1\Team;

use Illuminate\Foundation\Http\FormRequest;

class TransferCaptainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_captain_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
