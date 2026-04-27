<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    use ApiResponse;

    public function pending(): JsonResponse
    {
        $venues = Venue::where('status', 'inactive')
            ->orWhere('is_flagged', true)
            ->with(['club:id,name,slug'])
            ->paginate(20);

        return $this->success([
            'data' => $venues->items(),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'total' => $venues->total(),
            ],
        ]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $venue = Venue::findOrFail($id);
        $venue->update(['status' => 'active', 'is_flagged' => false]);

        AuditLog::record('venue.approved', $request->user()->id, $venue, [], $request->ip());

        return $this->success(['venue_id' => $venue->id, 'status' => 'active'], 'تمت الموافقة على الملعب');
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $venue = Venue::findOrFail($id);
        $venue->update(['status' => 'suspended']);

        AuditLog::record('venue.rejected', $request->user()->id, $venue, ['reason' => $reason], $request->ip());

        return $this->success(['venue_id' => $venue->id, 'status' => 'suspended'], 'تم رفض الملعب');
    }

    public function toggleFeatured(int $id, Request $request): JsonResponse
    {
        $venue = Venue::findOrFail($id);
        $venue->update(['is_featured' => ! $venue->is_featured]);

        AuditLog::record('venue.feature_toggled', $request->user()->id, $venue, ['featured' => $venue->is_featured], $request->ip());

        return $this->success(['venue_id' => $venue->id, 'is_featured' => (bool) $venue->is_featured]);
    }
}
