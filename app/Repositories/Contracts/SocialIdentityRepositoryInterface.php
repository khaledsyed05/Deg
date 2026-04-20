<?php

namespace App\Repositories\Contracts;

use App\Models\SocialIdentity;
use Illuminate\Database\Eloquent\Builder;

interface SocialIdentityRepositoryInterface
{
    public function find(int $id): ?SocialIdentity;

    public function findOrFail(int $id): SocialIdentity;

    public function create(array $data): SocialIdentity;

    public function update(SocialIdentity $model, array $data): SocialIdentity;

    public function delete(SocialIdentity $model): bool;

    public function query(): Builder;

    public function findByProvider(string $provider, string $providerUid): ?SocialIdentity;
}
