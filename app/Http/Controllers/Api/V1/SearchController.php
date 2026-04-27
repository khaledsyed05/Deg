<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SavedSearch;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function saveSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'filters' => 'required|array',
            'notify_new_venues' => 'sometimes|boolean',
        ]);

        $search = SavedSearch::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'filters' => $validated['filters'],
            'notify_new_venues' => $validated['notify_new_venues'] ?? false,
        ]);

        return $this->success($search, 'تم حفظ البحث بنجاح', 201);
    }

    public function getSavedSearches(): JsonResponse
    {
        return $this->success(auth()->user()->savedSearches()->latest()->get());
    }

    public function deleteSavedSearch(int $id): JsonResponse
    {
        $search = auth()->user()->savedSearches()->findOrFail($id);
        $search->delete();

        return $this->success(null, 'تم حذف البحث');
    }

    public function compareVenues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_ids' => 'required|array|min:2|max:3',
            'venue_ids.*' => 'integer|exists:venues,id',
        ]);

        $venues = Venue::whereIn('id', $validated['venue_ids'])
            ->with(['category', 'club'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        return $this->success([
            'venues' => $venues->map(fn ($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'category' => $venue->category?->name,
                'pricing' => [
                    'price_from' => $venue->price_from,
                ],
                'rating' => $venue->reviews_avg_rating ? round((float) $venue->reviews_avg_rating, 1) : null,
                'reviews_count' => $venue->reviews_count,
                'amenities' => $venue->amenities,
                'opening_hours' => $venue->opening_hours,
                'distance' => null,
            ])->values(),
        ]);
    }
}
