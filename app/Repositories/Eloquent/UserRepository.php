<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $model, array $data): User
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(User $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return User::query();
    }

    public function findByPhone(string $phoneNumber): ?User
    {
        return User::where('phone_number', $phoneNumber)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByFirebaseUid(string $uid): ?User
    {
        return User::where('firebase_uid', $uid)->first();
    }

    public function findClubStaff(int $clubId): Collection
    {
        return User::whereHas('clubs', fn (Builder $q) => $q->where('clubs.id', $clubId))->get();
    }
}
