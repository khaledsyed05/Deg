<?php

namespace App\Models;

use App\Enums\AchievementType;
use App\Notifications\AchievementUnlockedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'type' => AchievementType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnlocked(): bool
    {
        return ! is_null($this->unlocked_at);
    }

    public function unlock(): void
    {
        if ($this->isUnlocked()) {
            return;
        }

        $this->update(['unlocked_at' => now()]);

        try {
            $this->user?->notify(new AchievementUnlockedNotification($this));
        } catch (\Throwable $e) {
            \Log::error('Achievement notification failed', [
                'achievement_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateProgress(int $amount = 1): void
    {
        $this->increment('progress', $amount);

        if ($this->progress >= $this->target && ! $this->isUnlocked()) {
            $this->unlock();
        }
    }

    /**
     * @return array{title: string, description: string, icon: string, points: int, target: int}
     */
    public function getMetadata(): array
    {
        return $this->type->metadata();
    }
}
