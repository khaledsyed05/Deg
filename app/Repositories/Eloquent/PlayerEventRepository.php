<?php

namespace App\Repositories\Eloquent;

use App\Models\PlayerEvent;
use App\Repositories\Contracts\PlayerEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class PlayerEventRepository implements PlayerEventRepositoryInterface
{
    public function find(int $id): ?PlayerEvent
    {
        return PlayerEvent::find($id);
    }

    public function findOrFail(int $id): PlayerEvent
    {
        return PlayerEvent::findOrFail($id);
    }

    public function create(array $data): PlayerEvent
    {
        return PlayerEvent::create($data);
    }

    public function update(PlayerEvent $model, array $data): PlayerEvent
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(PlayerEvent $model): bool
    {
        return $model->delete();
    }

    public function query(): Builder
    {
        return PlayerEvent::query();
    }

    public function record(string $eventName, array $properties = [], ?int $userId = null, ?string $anonymousId = null, string $sessionId = ''): PlayerEvent
    {
        return PlayerEvent::create([
            'event_name' => $eventName,
            'properties' => $properties ?: null,
            'user_id' => $userId,
            'anonymous_id' => $anonymousId,
            'session_id' => $sessionId,
            'occurred_at' => now(),
        ]);
    }
}
