<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Exceptions\Moderation\ModerationException;
use App\Exceptions\Review\ReviewException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Review\CreateReviewRequest;
use App\Http\Requests\Api\V1\Review\ReportReviewRequest;
use App\Http\Requests\Api\V1\Review\UpdateReviewRequest;
use App\Http\Requests\Api\V1\Review\UploadReviewPhotosRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Services\Moderation\ReviewReportService;
use App\Services\Review\ReviewPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReviewRepositoryInterface $reviewRepo,
    ) {}

    /**
     * Submit a review for a completed booking. One review per booking.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $booking = Booking::find($request->integer('booking_id'));

        if (! $booking || $booking->user_id !== $request->user()->id) {
            return $this->error('غير مصرح لك بتقييم هذا الحجز', null, 403);
        }

        if ($booking->status !== BookingStatus::Completed) {
            return $this->error('لا يمكن تقييم حجز غير مكتمل', null, 422);
        }

        if ($booking->review()->exists()) {
            return $this->error('لقد قمت بتقييم هذا الحجز مسبقاً', null, 409);
        }

        $review = $this->reviewRepo->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $booking->forceFill(['reviewed_at' => now()])->save();

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review->load(['user', 'venue'])),
        ], 201);
    }

    /**
     * Update an existing review (within 7-day edit window).
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorize('update', $review);

        $review = $this->reviewRepo->update($review, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review->load(['user', 'venue'])),
        ]);
    }

    /**
     * Delete a review (within 7-day edit window, or admin).
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->reviewRepo->delete($review);

        return response()->json(['success' => true]);
    }

    /**
     * Authenticated user's reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = Review::forUser($request->user()->id)
            ->with(['user', 'venue', 'booking:id,booking_code,booking_date,venue_id'])
            ->recent()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Bookings awaiting review.
     */
    public function pending(Request $request): JsonResponse
    {
        $bookings = Booking::forUser($request->user()->id)
            ->where('status', BookingStatus::Completed)
            ->whereDoesntHave('review')
            ->with('venue:id,slug,name')
            ->latest('booking_date')
            ->limit(50)
            ->get();

        $payload = $bookings->map(fn (Booking $booking) => [
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'venue' => $booking->venue ? [
                'id' => $booking->venue->id,
                'slug' => $booking->venue->slug,
                'name' => $booking->venue->getTranslations('name'),
            ] : null,
            'booking_date' => $booking->booking_date?->toDateString(),
        ])->values();

        return $this->success(['pending_reviews' => $payload]);
    }

    /**
     * Toggle "helpful" vote on a review. One vote per user per review.
     */
    public function helpful(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();

        if ($review->user_id === $user->id) {
            return $this->error('لا يمكنك تقييم مراجعتك الخاصة', null, 400);
        }

        $isHelpful = $review->toggleHelpful($user);

        return $this->success([
            'is_helpful' => $isHelpful,
            'helpful_count' => $review->fresh()->helpful_count,
        ], $isHelpful ? 'تم التصويت بنجاح' : 'تم إلغاء التصويت');
    }

    public function report(int $id, ReportReviewRequest $request, ReviewReportService $service): JsonResponse
    {
        $review = Review::findOrFail($id);

        try {
            $report = $service->report(
                $review,
                $request->user(),
                $request->validated('reason'),
                $request->validated('description'),
            );
        } catch (ModerationException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'report_id' => $report->id,
            'status' => $report->status,
        ], 'تم استلام التقرير', 201);
    }

    public function uploadPhotos(int $id, UploadReviewPhotosRequest $request, ReviewPhotoService $service): JsonResponse
    {
        $review = Review::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $photos = $service->uploadPhotos($review, $request->file('photos'));
        } catch (ReviewException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'review_id' => $review->id,
            'photos' => $photos,
            'total_photos' => count($photos),
        ], 'تم رفع الصور بنجاح', 201);
    }

    public function guidelines(): JsonResponse
    {
        $guidelines = Cache::remember('review_guidelines', 86400, fn () => [
            'version' => '1.0',
            'last_updated' => '2026-04-25',
            'guidelines' => [
                [
                    'category' => 'what_to_review',
                    'title_ar' => 'ماذا تراجع',
                    'items' => [
                        'نظافة الملعب والمرافق',
                        'جودة الأرضية والمعدات',
                        'خدمة العملاء',
                        'الموقع وسهولة الوصول',
                        'السعر والقيمة',
                    ],
                ],
                [
                    'category' => 'writing_tips',
                    'title_ar' => 'نصائح للكتابة',
                    'items' => [
                        'كن صادقاً ومحدداً',
                        'اذكر التاريخ والوقت إذا كان ذلك مفيداً',
                        'ركّز على تجربتك الشخصية',
                        'استخدم لغة واضحة ومحترمة',
                    ],
                ],
                [
                    'category' => 'what_to_avoid',
                    'title_ar' => 'ما يجب تجنبه',
                    'items' => [
                        'لا تستخدم لغة مسيئة أو مهينة',
                        'لا تشارك معلومات شخصية للآخرين',
                        'لا تنشر صور غير لائقة',
                        'لا تكرر التقييمات',
                        'لا تكتب تقييمات وهمية',
                    ],
                ],
                [
                    'category' => 'rules',
                    'title_ar' => 'القواعد',
                    'items' => [
                        'يجب أن تكون قد حجزت في الملعب',
                        'تقييم واحد فقط لكل حجز',
                        'يمكن تعديل التقييم خلال 7 أيام',
                        'نحتفظ بحق إزالة التقييمات المخالفة',
                    ],
                ],
            ],
            'rating_explanation' => [
                ['stars' => 5, 'label_ar' => 'ممتاز', 'description_ar' => 'تجربة استثنائية، أنصح بشدة'],
                ['stars' => 4, 'label_ar' => 'جيد جداً', 'description_ar' => 'تجربة جيدة، ينصح به'],
                ['stars' => 3, 'label_ar' => 'جيد', 'description_ar' => 'تجربة عادية'],
                ['stars' => 2, 'label_ar' => 'ضعيف', 'description_ar' => 'تجربة دون المتوقع'],
                ['stars' => 1, 'label_ar' => 'سيء', 'description_ar' => 'تجربة سيئة، لا ننصح به'],
            ],
        ]);

        return $this->success($guidelines);
    }
}
