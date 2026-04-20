<?php

namespace App\Repositories\Eloquent;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Repositories\Contracts\ClubRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ClubRepository implements ClubRepositoryInterface
{
    public function find(int $id): ?Club
    {
        return Club::find($id);
    }

    public function findOrFail(int $id): Club
    {
        return Club::findOrFail($id);
    }

    public function create(array $data): Club
    {
        return Club::create($data);
    }

    public function update(Club $model, array $data): Club
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Club $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return Club::query();
    }

    public function findActive(): Collection
    {
        return Club::where('status', ClubStatus::Active)->get();
    }

    public function findPendingApproval(): Collection
    {
        return Club::where('status', ClubStatus::PendingApproval)->get();
    }

    public function findByCity(int $cityId): Collection
    {
        return Club::where('city_id', $cityId)->get();
    }

    public function findFeatured(): Collection
    {
        return Club::where('is_featured', true)
            ->where('status', ClubStatus::Active)
            ->get();
    }

    public function approve(Club $club, int $adminId): Club
    {
        $club->update([
            'status' => ClubStatus::Active,
            'approved_at' => now(),
            'approved_by' => $adminId,
            'rejection_reason' => null,
        ]);

        return $club->fresh();
    }

    public function reject(Club $club, string $reason): Club
    {
        $club->update([
            'status' => ClubStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        return $club->fresh();
    }
}
