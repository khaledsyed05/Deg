<?php

namespace App\Repositories\Eloquent;

use App\Enums\CommissionScope;
use App\Models\CommissionConfig;
use App\Models\Venue;
use App\Repositories\Contracts\CommissionConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CommissionConfigRepository implements CommissionConfigRepositoryInterface
{
    public function find(int $id): ?CommissionConfig
    {
        return CommissionConfig::find($id);
    }

    public function findOrFail(int $id): CommissionConfig
    {
        return CommissionConfig::findOrFail($id);
    }

    public function create(array $data): CommissionConfig
    {
        return CommissionConfig::create($data);
    }

    public function update(CommissionConfig $model, array $data): CommissionConfig
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(CommissionConfig $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return CommissionConfig::query();
    }

    public function findGlobalActive(): ?CommissionConfig
    {
        return CommissionConfig::where('scope', CommissionScope::Global)
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();
    }

    public function findForClub(int $clubId): ?CommissionConfig
    {
        return CommissionConfig::where('scope', CommissionScope::Club)
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();
    }

    public function findForVenue(int $venueId): ?CommissionConfig
    {
        // Resolve: venue → club → global (most specific wins)
        $venueConfig = CommissionConfig::where('scope', CommissionScope::Venue)
            ->where('venue_id', $venueId)
            ->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        if ($venueConfig) {
            return $venueConfig;
        }

        $venue = Venue::find($venueId);
        if ($venue) {
            $clubConfig = $this->findForClub($venue->club_id);
            if ($clubConfig) {
                return $clubConfig;
            }
        }

        return $this->findGlobalActive();
    }
}
