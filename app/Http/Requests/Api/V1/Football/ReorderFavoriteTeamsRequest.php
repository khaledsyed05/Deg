<?php

namespace App\Http\Requests\Api\V1\Football;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFavoriteTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['integer', 'exists:football_teams,id'],
        ];
    }
}
