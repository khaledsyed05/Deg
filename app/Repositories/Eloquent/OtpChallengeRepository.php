<?php

namespace App\Repositories\Eloquent;

use App\Models\OtpChallenge;
use App\Repositories\Contracts\OtpChallengeRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class OtpChallengeRepository implements OtpChallengeRepositoryInterface
{
    public function find(int $id): ?OtpChallenge
    {
        return OtpChallenge::find($id);
    }

    public function findOrFail(int $id): OtpChallenge
    {
        return OtpChallenge::findOrFail($id);
    }

    public function create(array $data): OtpChallenge
    {
        return OtpChallenge::create($data);
    }

    public function update(OtpChallenge $model, array $data): OtpChallenge
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(OtpChallenge $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return OtpChallenge::query();
    }

    public function findByUuid(string $uuid): ?OtpChallenge
    {
        return OtpChallenge::where('uuid', $uuid)->first();
    }

    public function deleteExpired(): int
    {
        return OtpChallenge::where('expires_at', '<=', now())->delete();
    }
}
