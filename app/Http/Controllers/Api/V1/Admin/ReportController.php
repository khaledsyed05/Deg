<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\ReviewReport;
use App\Models\VenueReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function venues(Request $request): JsonResponse
    {
        $query = VenueReport::query()->with(['venue:id,name,slug', 'user:id,name']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(20);

        return $this->success([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function venueAction(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:hide_venue,ban_owner,dismiss',
            'notes' => 'sometimes|nullable|string|max:500',
        ]);

        $report = VenueReport::findOrFail($id);
        $venue = $report->venue;

        match ($data['action']) {
            'hide_venue' => $venue?->update(['status' => 'suspended']),
            'ban_owner' => $venue?->club?->owner?->update(['account_status' => 'banned', 'blocked_at' => now()]),
            default => null,
        };

        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['notes'] ?? null,
        ]);

        AuditLog::record('venue_report.action', $request->user()->id, $report, $data, $request->ip());

        return $this->success(['report_id' => $report->id, 'status' => $report->status], 'تم اتخاذ الإجراء');
    }

    public function reviews(Request $request): JsonResponse
    {
        $query = ReviewReport::query()->with(['review.user:id,name', 'user:id,name']);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(20);

        return $this->success([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function reviewAction(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:hide_review,delete_review,dismiss',
            'notes' => 'sometimes|nullable|string|max:500',
        ]);

        $report = ReviewReport::findOrFail($id);
        $review = $report->review;

        match ($data['action']) {
            'hide_review' => $review?->update(['is_hidden' => true, 'hidden_at' => now()]),
            'delete_review' => $review?->delete(),
            default => null,
        };

        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['notes'] ?? null,
        ]);

        AuditLog::record('review_report.action', $request->user()->id, $report, $data, $request->ip());

        return $this->success(['report_id' => $report->id, 'status' => $report->status], 'تم اتخاذ الإجراء');
    }
}
