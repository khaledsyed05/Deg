<?php

namespace App\Services\Analytics;

use App\Repositories\Contracts\PlayerEventRepositoryInterface;

class PlayerEventService
{
    public function __construct(
        private PlayerEventRepositoryInterface $eventRepo,
    ) {}

    public function track(
        string $eventName,
        array $properties = [],
        ?int $userId = null,
        ?string $anonymousId = null,
        string $sessionId = '',
    ): void {
        $this->eventRepo->record($eventName, $properties, $userId, $anonymousId, $sessionId);
    }

    /**
     * Batch track multiple events (e.g. from mobile offline queue flush).
     *
     * @param  array<array{event_name: string, properties: array, occurred_at: string}>  $events
     */
    public function trackBatch(array $events, ?int $userId, ?string $anonymousId, string $sessionId): void
    {
        foreach ($events as $event) {
            $this->eventRepo->create([
                'event_name' => $event['event_name'],
                'properties' => $event['properties'] ?? null,
                'user_id' => $userId,
                'anonymous_id' => $anonymousId,
                'session_id' => $sessionId,
                'occurred_at' => $event['occurred_at'] ?? now(),
            ]);
        }
    }
}
