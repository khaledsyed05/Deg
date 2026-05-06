<?php

namespace App\Http\Resources\Team;

use App\Models\TeamInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeamInvite
 */
class TeamInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'code' => $this->code,
            'created_by' => $this->created_by,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'max_uses' => $this->max_uses !== null ? (int) $this->max_uses : null,
            'uses_count' => (int) $this->uses_count,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
