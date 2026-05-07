<?php

namespace App\Http\Resources\Team;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'sport_category_id' => $this->sport_category_id,
            'captain_id' => $this->captain_id,
            'max_members' => (int) $this->max_members,
            'is_public' => (bool) $this->is_public,
            'requires_approval' => (bool) $this->requires_approval,
            'avatar_url' => $this->avatar_url,
            'total_members' => (int) $this->total_members,
            'total_bookings' => (int) $this->total_bookings,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
