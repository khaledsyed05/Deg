<?php

namespace App\Repositories\Contracts;

use App\Models\AppPlatform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface AppPlatformRepositoryInterface
{
    public function find(int $id): ?AppPlatform;

    public function findOrFail(int $id): AppPlatform;

    public function create(array $data): AppPlatform;

    public function update(AppPlatform $model, array $data): AppPlatform;

    public function delete(AppPlatform $model): bool;

    public function query(): Builder;

    public function findEnabled(): Collection;

    public function findByKey(string $platformKey): ?AppPlatform;
}
