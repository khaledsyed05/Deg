<?php

namespace App\Http\Requests\Api\V1\Event;

use Illuminate\Foundation\Http\FormRequest;

class RegisterForEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'team_id' => 'sometimes|nullable|integer|exists:teams,id',
            'participant_info' => 'sometimes|nullable|array',
            'participant_info.age' => 'sometimes|integer|min:5|max:100',
            'participant_info.jersey_number' => 'sometimes|integer|min:1|max:99',
            'participant_info.position' => 'sometimes|string|max:50',
            'participant_info.skill_level' => 'sometimes|in:beginner,intermediate,advanced,professional',
        ];
    }
}
