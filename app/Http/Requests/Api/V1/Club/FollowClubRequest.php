<?php

namespace App\Http\Requests\Api\V1\Club;

use Illuminate\Foundation\Http\FormRequest;

class FollowClubRequest extends FormRequest
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
            'notify_updates' => 'sometimes|boolean',
            'notify_events' => 'sometimes|boolean',
            'notify_promotions' => 'sometimes|boolean',
        ];
    }
}
