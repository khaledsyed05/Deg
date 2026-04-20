<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Review\CreateReviewRequest;
use App\Http\Requests\Api\V1\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
    ) {}

    /**
     * Submit a review
     *
     * Creates a new review for a completed booking. One review per booking is allowed.
     *
     * @bodyParam booking_id integer required The completed booking ID being reviewed. Example: 123
     * @bodyParam rating integer required Rating from 1 to 5. Example: 5
     * @bodyParam comment string Optional review text (max 1000 chars). Example: ملعب رائع
     *
     * @response 201 {"success": true, "data": {"id": 1, "rating": 5, "comment": "ملعب رائع"}}
     * @response 422 {"success": false, "errors": {"rating": ["التقييم مطلوب"]}}
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $review = $this->reviewRepo->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update a review
     *
     * Updates an existing review. Users can only update their own reviews.
     *
     * @bodyParam rating integer Rating from 1 to 5. Example: 4
     * @bodyParam comment string Updated review text. Example: تجربة جيدة
     *
     * @response 200 {"success": true, "data": {"id": 1, "rating": 4}}
     * @response 403 {"message": "Forbidden"}
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorize('update', $review);

        $review = $this->reviewRepo->update($review, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete a review
     *
     * Permanently deletes a review. Users can only delete their own reviews.
     *
     * @response 200 {"success": true}
     * @response 403 {"message": "Forbidden"}
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->reviewRepo->delete($review);

        return response()->json(['success' => true]);
    }
}
