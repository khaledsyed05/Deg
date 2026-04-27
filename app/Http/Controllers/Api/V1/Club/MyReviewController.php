<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Club;
use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyReviewController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        $reviews = Review::whereIn('venue_id', $venueIds)
            ->where('is_published', true)
            ->with(['user:id,name', 'venue:id,name'])
            ->latest()
            ->paginate(20);

        $reviewIds = collect($reviews->items())->pluck('id');
        $responses = ReviewResponse::whereIn('review_id', $reviewIds)->get()->keyBy('review_id');

        $items = collect($reviews->items())->map(function ($r) use ($responses) {
            $arr = $r->toArray();
            $arr['club_response'] = $responses->get($r->id)?->only(['id', 'response', 'created_at']);

            return $arr;
        });

        return $this->success([
            'data' => $items,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function respond(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venueIds = Club::findOrFail($clubId)->venues()->pluck('id');

        $review = Review::whereIn('venue_id', $venueIds)->findOrFail($id);

        $data = $request->validate([
            'response' => 'required|string|min:5|max:2000',
        ]);

        $response = ReviewResponse::updateOrCreate(
            ['review_id' => $review->id],
            [
                'club_id' => $clubId,
                'responded_by' => $request->user()->id,
                'response' => $data['response'],
            ]
        );

        return $this->success([
            'review_id' => $review->id,
            'response_id' => $response->id,
            'response' => $response->response,
        ], 'تم إرسال الرد', 201);
    }
}
