<?php

namespace App\Http\Requests\Api\V1\Football;

use Illuminate\Foundation\Http\FormRequest;

class FollowLeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'league_code' => ['required', 'string', 'exists:football_leagues,code'],
            'notify_matches' => ['sometimes', 'boolean'],
        ];
    }
}
