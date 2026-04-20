<?php

namespace App\Repositories\Contracts;

use App\Models\PlayerEvent;
use Illuminate\Database\Eloquent\Builder;

interface PlayerEventRepositoryInterface
{
    public function find(int $id): ?PlayerEvent;

    public function findOrFail(int $id): PlayerEvent;

    public function create(array $data): PlayerEvent;

    public function update(PlayerEvent $model, array $data): PlayerEvent;

    public function delete(PlayerEvent $model): bool;

    public function query(): Builder;

    public function record(string $eventName, array $properties = [], ?int $userId = null, ?string $anonymousId = null, string $sessionId = ''): PlayerEvent;
}
