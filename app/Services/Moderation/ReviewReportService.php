<?php

namespace App\Services\Moderation;

use App\Exceptions\Moderation\ModerationException;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewReportService
{
    public function report(Review $review, User $user, string $reason, ?string $description = null): ReviewReport
    {
        if ($review->user_id === $user->id) {
            throw new ModerationException('Cannot report your own review', 422);
        }

        if (ReviewReport::where('review_id', $review->id)->where('user_id', $user->id)->exists()) {
            throw new ModerationException('You already reported this review', 422);
        }

        return DB::transaction(function () use ($review, $user, $reason, $description) {
            $report = ReviewReport::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'description' => $description,
                'status' => 'pending',
            ]);

            $review->increment('reports_count');

            $threshold = (int) config('bookings.moderation.auto_hide_review_threshold', 3);
            if ((int) $review->fresh()->reports_count >= $threshold && ! $review->is_hidden) {
                $review->update(['is_hidden' => true]);
            }

            return $report;
        });
    }
}
