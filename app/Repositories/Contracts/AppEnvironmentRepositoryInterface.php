<?php

namespace App\Repositories\Contracts;

use App\Models\AppEnvironment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface AppEnvironmentRepositoryInterface
{
    public function find(int $id): ?AppEnvironment;

    public function findOrFail(int $id): AppEnvironment;

    public function create(array $data): AppEnvironment;

    public function update(AppEnvironment $model, array $data): AppEnvironment;

    public function delete(AppEnvironment $model): bool;

    public function query(): Builder;

    public function findByPlatform(int $platformId): Collection;

    public function findActiveByPlatform(int $platformId): Collection;
}
