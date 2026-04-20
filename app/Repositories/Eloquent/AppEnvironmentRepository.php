<?php

namespace App\Repositories\Eloquent;

use App\Models\AppEnvironment;
use App\Repositories\Contracts\AppEnvironmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AppEnvironmentRepository implements AppEnvironmentRepositoryInterface
{
    public function find(int $id): ?AppEnvironment
    {
        return AppEnvironment::find($id);
    }

    public function findOrFail(int $id): AppEnvironment
    {
        return AppEnvironment::findOrFail($id);
    }

    public function create(array $data): AppEnvironment
    {
        return AppEnvironment::create($data);
    }

    public function update(AppEnvironment $model, array $data): AppEnvironment
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(AppEnvironment $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return AppEnvironment::query();
    }

    public function findByPlatform(int $platformId): Collection
    {
        return AppEnvironment::where('platform_id', $platformId)->get();
    }

    public function findActiveByPlatform(int $platformId): Collection
    {
        return AppEnvironment::where('platform_id', $platformId)->where('is_active', true)->get();
    }
}
