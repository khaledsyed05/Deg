<?php

namespace App\Http\Requests\Api\V1\Football;

use Illuminate\Foundation\Http\FormRequest;

class AddFavoriteTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:football_teams,id'],
            'notify_matches' => ['sometimes', 'boolean'],
            'notify_goals' => ['sometimes', 'boolean'],
            'notify_results' => ['sometimes', 'boolean'],
        ];
    }
}
