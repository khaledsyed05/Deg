<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'avatar_url' => $this->getAvatarUrl(),
            'city' => $this->defaultCity ? [
                'id' => $this->defaultCity->id,
                'name' => $this->defaultCity->name,
                'name_ar' => $this->defaultCity->name_ar,
            ] : null,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'is_phone_verified' => ! is_null($this->phone_verified_at),
            'has_google' => ! is_null($this->firebase_uid),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
