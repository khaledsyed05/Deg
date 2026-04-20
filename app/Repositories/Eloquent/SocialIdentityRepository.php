<?php

namespace App\Repositories\Eloquent;

use App\Models\SocialIdentity;
use App\Repositories\Contracts\SocialIdentityRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class SocialIdentityRepository implements SocialIdentityRepositoryInterface
{
    public function find(int $id): ?SocialIdentity
    {
        return SocialIdentity::find($id);
    }

    public function findOrFail(int $id): SocialIdentity
    {
        return SocialIdentity::findOrFail($id);
    }

    public function create(array $data): SocialIdentity
    {
        return SocialIdentity::create($data);
    }

    public function update(SocialIdentity $model, array $data): SocialIdentity
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(SocialIdentity $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return SocialIdentity::query();
    }

    public function findByProvider(string $provider, string $providerUid): ?SocialIdentity
    {
        return SocialIdentity::where('provider', $provider)
            ->where('provider_uid', $providerUid)
            ->first();
    }
}
