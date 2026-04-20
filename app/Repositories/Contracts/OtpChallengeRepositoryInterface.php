<?php

namespace App\Repositories\Contracts;

use App\Models\OtpChallenge;
use Illuminate\Database\Eloquent\Builder;

interface OtpChallengeRepositoryInterface
{
    public function find(int $id): ?OtpChallenge;

    public function findOrFail(int $id): OtpChallenge;

    public function create(array $data): OtpChallenge;

    public function update(OtpChallenge $model, array $data): OtpChallenge;

    public function delete(OtpChallenge $model): bool;

    public function query(): Builder;

    public function findByUuid(string $uuid): ?OtpChallenge;

    public function deleteExpired(): int;
}
