<?php

namespace App\Http\Resources\Football;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'name_ar' => $this->name_ar,
            'tla' => $this->tla,
            'crest_url' => $this->crest_url,
            'country' => $this->country,
            'venue_name' => $this->venue_name,
            'founded_year' => $this->founded_year,
            'is_popular' => (bool) $this->is_popular,
            'league' => $this->whenLoaded('league', fn () => new LeagueResource($this->league)),
            'pivot' => $this->whenPivotLoaded('user_favorite_teams', fn () => [
                'display_order' => $this->pivot->display_order,
                'notify_matches' => (bool) $this->pivot->notify_matches,
                'notify_goals' => (bool) $this->pivot->notify_goals,
                'notify_results' => (bool) $this->pivot->notify_results,
            ]),
        ];
    }
}
