<?php

namespace App\Jobs\Club;

use App\Models\Club;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateClubRatingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public int $clubId,
    ) {}

    public function handle(ReviewRepositoryInterface $reviewRepo): void
    {
        $club = Club::find($this->clubId);

        if (! $club) {
            return;
        }

        $reviews = $reviewRepo->findByClub($this->clubId, publishedOnly: true);

        if ($reviews->isEmpty()) {
            $club->update([
                'avg_rating' => null,
                'reviews_count' => 0,
            ]);
        } else {
            $club->update([
                'avg_rating' => round($reviews->avg('rating'), 2),
                'reviews_count' => $reviews->count(),
            ]);
        }
    }
}
