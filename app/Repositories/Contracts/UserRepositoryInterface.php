<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;

    public function findOrFail(int $id): User;

    public function create(array $data): User;

    public function update(User $model, array $data): User;

    public function delete(User $model): bool;

    public function query(): Builder;

    public function findByPhone(string $phoneNumber): ?User;

    public function findByEmail(string $email): ?User;

    public function findByFirebaseUid(string $uid): ?User;

    public function findClubStaff(int $clubId): Collection;
}
