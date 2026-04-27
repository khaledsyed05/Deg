<?php

namespace App\Services\Football;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiSportsKeyManager
{
    /** @var array<int, string> */
    private array $keys;

    public function __construct()
    {
        $this->keys = array_values(array_filter([
            config('services.api_sports.key_1'),
            config('services.api_sports.key_2'),
            config('services.api_sports.key_3'),
        ]));
    }

    public function getActiveKey(): ?string
    {
        if (empty($this->keys)) {
            Log::channel('football')->warning('No API-Sports keys configured. Live score features disabled.');

            return null;
        }

        $currentIndex = (int) Cache::get('api_sports_key_index', 0);
        $currentKey = $this->keys[$currentIndex] ?? null;

        if (! $currentKey) {
            $currentIndex = 0;
            $currentKey = $this->keys[0];
        }

        $remaining = $this->getRemainingQuota($currentKey);

        if ($remaining < 5 && count($this->keys) > 1) {
            $currentIndex = ($currentIndex + 1) % count($this->keys);
            Cache::put('api_sports_key_index', $currentIndex, now()->endOfDay());
            $currentKey = $this->keys[$currentIndex];

            Log::channel('football')->info('Switched API-Sports key', [
                'new_index' => $currentIndex,
                'previous_remaining' => $remaining,
            ]);
        }

        return $currentKey;
    }

    public function getRemainingQuota(string $key): int
    {
        $cacheKey = 'api_sports_quota_'.md5($key);

        return (int) Cache::remember($cacheKey, 60, function () use ($key) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['x-apisports-key' => $key])
                    ->get(rtrim((string) config('services.api_sports.base_url'), '/').'/status');

                if (! $response->successful()) {
                    return 0;
                }

                $data = $response->json('response.requests', []);

                return (int) (($data['limit_day'] ?? 100) - ($data['current'] ?? 0));
            } catch (\Throwable $e) {
                Log::channel('football')->error('Failed to check API-Sports quota', [
                    'error' => $e->getMessage(),
                ]);

                return 0;
            }
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllKeysStatus(): array
    {
        $result = [];
        foreach ($this->keys as $index => $key) {
            $result[] = [
                'index' => $index,
                'key_preview' => substr($key, 0, 8).'...',
                'remaining' => $this->getRemainingQuota($key),
            ];
        }

        return $result;
    }

    public function hasKeys(): bool
    {
        return ! empty($this->keys);
    }
}
