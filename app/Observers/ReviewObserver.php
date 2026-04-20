<?php

namespace App\Observers;

use App\Jobs\Club\UpdateClubRatingJob;
use App\Models\Review;
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
    }

    public function updated(Review $review): void
    {
        if (! $review->isDirty('rating') && ! $review->isDirty('is_published')) {
            return;
        }

        try {
            UpdateClubRatingJob::dispatch($review->club_id);
        } catch (\Throwable $e) {
            Log::error('ReviewObserver: failed to dispatch rating update on update', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
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
    }
}
