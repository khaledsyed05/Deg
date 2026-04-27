<?php

namespace App\Http\Requests\Api\V1\Football;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFollowedLeaguesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'league_ids' => ['required', 'array', 'min:1'],
            'league_ids.*' => ['integer', 'exists:football_leagues,id'],
        ];
    }
}
