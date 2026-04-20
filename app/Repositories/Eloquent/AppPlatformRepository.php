<?php

namespace App\Repositories\Eloquent;

use App\Models\AppPlatform;
use App\Repositories\Contracts\AppPlatformRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AppPlatformRepository implements AppPlatformRepositoryInterface
{
    public function find(int $id): ?AppPlatform
    {
        return AppPlatform::find($id);
    }

    public function findOrFail(int $id): AppPlatform
    {
        return AppPlatform::findOrFail($id);
    }

    public function create(array $data): AppPlatform
    {
        return AppPlatform::create($data);
    }

    public function update(AppPlatform $model, array $data): AppPlatform
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(AppPlatform $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return AppPlatform::query();
    }

    public function findEnabled(): Collection
    {
        return AppPlatform::where('enabled', true)->orderBy('order_column')->get();
    }

    public function findByKey(string $platformKey): ?AppPlatform
    {
        return AppPlatform::where('platform_key', $platformKey)->first();
    }
}
