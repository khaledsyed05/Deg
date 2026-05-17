<?php

namespace App\Http\Requests\Api\V1\Football;

use App\Models\Football\MatchActivityToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMatchActivityTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::in([MatchActivityToken::PLATFORM_IOS, MatchActivityToken::PLATFORM_ANDROID])],
            'push_token' => ['required', 'string', 'max:4096'],
            'activity_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
