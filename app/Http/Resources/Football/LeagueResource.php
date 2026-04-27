<?php

namespace App\Http\Resources\Football;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'code' => $this->code,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'country' => $this->country,
            'country_ar' => $this->country_ar,
            'emblem_url' => $this->emblem_url,
            'type' => $this->type,
            'is_featured' => (bool) $this->is_featured,
            'current_season_start' => $this->current_season_start?->toDateString(),
            'current_season_end' => $this->current_season_end?->toDateString(),
        ];
    }
}
