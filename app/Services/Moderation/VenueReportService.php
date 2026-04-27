<?php

namespace App\Services\Moderation;

use App\Exceptions\Moderation\ModerationException;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VenueReportService
{
    public function report(Venue $venue, User $user, string $reason, string $description, array $evidenceUrls = []): VenueReport
    {
        if (VenueReport::where('venue_id', $venue->id)->where('user_id', $user->id)->exists()) {
            throw new ModerationException('You already reported this venue', 422);
        }

        return DB::transaction(function () use ($venue, $user, $reason, $description, $evidenceUrls) {
            $report = VenueReport::create([
                'venue_id' => $venue->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'description' => $description,
                'evidence_urls' => $evidenceUrls ?: null,
                'status' => 'pending',
            ]);

            $venue->increment('reports_count');

            $threshold = (int) config('bookings.moderation.auto_flag_venue_threshold', 5);
            if ((int) $venue->fresh()->reports_count >= $threshold && ! $venue->is_flagged) {
                $venue->update(['is_flagged' => true]);
                Log::channel('single')->warning('Venue auto-flagged', [
                    'venue_id' => $venue->id,
                    'reports_count' => $venue->fresh()->reports_count,
                ]);
            }

            return $report;
        });
    }
}
