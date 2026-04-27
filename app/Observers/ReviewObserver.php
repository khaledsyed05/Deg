<?php

namespace App\Observers;

use App\Enums\AchievementType;
use App\Jobs\Club\UpdateClubRatingJob;
use App\Models\PlayerStats;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    public function created(Review $review): void
    {
        try {
            UpdateClubRatingJob::dispatch($review->club_id);
        } catch (\Throwable $e) {
            Log::error('ReviewObserver: failed to dispatch rating update on create', [
                'review_id' => $review->id,
                'club_id' => $review->club_id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($review->user) {
            $this->updateAverageRating($review->user);
            $this->checkReviewChampion($review->user);
        }
    }

    public function updated(Review $review): void
    {
        if ($review->isDirty('rating') || $review->isDirty('is_published')) {
            try {
                UpdateClubRatingJob::dispatch($review->club_id);
            } catch (\Throwable $e) {
                Log::error('ReviewObserver: failed to dispatch rating update on update', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($review->isDirty('rating') && $review->user) {
            $this->updateAverageRating($review->user);
        }
    }

    public function deleted(Review $review): void
    {
        try {
            UpdateClubRatingJob::dispatch($review->club_id);
        } catch (\Throwable $e) {
            Log::error('ReviewObserver: failed to dispatch rating update on delete', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($review->user) {
            $this->updateAverageRating($review->user);
        }
    }

    protected function updateAverageRating(User $user): void
    {
        try {
            $avg = $user->reviews()->avg('rating');
            $stats = PlayerStats::firstOrCreate(['user_id' => $user->id]);
            $stats->update([
                'average_rating_given' => $avg !== null ? round((float) $avg, 2) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('ReviewObserver: failed to update average rating', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkReviewChampion(User $user): void
    {
        try {
            $count = (int) $user->reviews()->count();

            $achievement = $user->achievements()->firstOrCreate(
                ['type' => AchievementType::ReviewChampion->value],
                ['progress' => 0, 'target' => 25],
            );

            $achievement->progress = $count;
            $achievement->save();

            if ($count >= 25 && ! $achievement->isUnlocked()) {
                $achievement->unlock();
            }
        } catch (\Throwable $e) {
            Log::error('ReviewObserver: failed to check review champion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
