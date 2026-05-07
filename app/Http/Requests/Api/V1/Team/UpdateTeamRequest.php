<?php

namespace App\Http\Requests\Api\V1\Team;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
            'is_public' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'max_members' => ['sometimes', 'integer', 'min:2', 'max:50'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'regular_schedule' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
