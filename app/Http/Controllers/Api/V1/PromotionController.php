<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Promotion\PromotionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Promotion\ValidatePromotionRequest;
use App\Http\Resources\V1\Promotion\PromotionResource;
use App\Http\Traits\ApiResponse;
use App\Models\Promotion;
use App\Models\PromoUsage;
use App\Models\Venue;
use App\Services\Promotion\PromotionRemovalService;
use App\Services\Promotion\QrPromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponse;

    /**
     * List currently active promotions. Optional filter by venue.
     */
    public function index(Request $request): JsonResponse
    {
        $venueId = $request->integer('venue_id') ?: null;

        $query = Promotion::query()->active()->with('venue');

        if ($venueId) {
            $query->forVenue($venueId);
        }

        return $this->success(PromotionResource::collection($query->latest()->limit(50)->get()));
    }

    /**
     * Featured promotions (curated).
     */
    public function featured(): JsonResponse
    {
        $promotions = Promotion::query()
            ->active()
            ->featured()
            ->with('venue')
            ->latest()
            ->limit(10)
            ->get();

        return $this->success(PromotionResource::collection($promotions));
    }

    /**
     * Look up a promo code.
     */
    public function show(string $code): JsonResponse
    {
        $promotion = Promotion::where('code', strtoupper($code))->with('venue')->first();

        if (! $promotion) {
            return $this->notFound('كود الخصم غير صحيح');
        }

        return $this->success(new PromotionResource($promotion));
    }

    /**
     * Validate a promo code against a prospective booking.
     */
    public function validateCode(string $code, ValidatePromotionRequest $request): JsonResponse
    {
        $promotion = Promotion::where('code', strtoupper($code))->first();

        if (! $promotion) {
            return $this->error('كود الخصم غير صحيح', null, 404);
        }

        $result = $promotion->validateForBooking(
            $request->user(),
            (int) $request->input('venue_id'),
            (int) $request->input('booking_amount'),
            $request->input('booking_date'),
        );

        if (! $result['valid']) {
            return $this->error($result['message'], null, 422);
        }

        return $this->success([
            'valid' => true,
            'promotion' => new PromotionResource($result['promotion']),
            'discount_amount' => $result['discount_amount'],
            'final_amount' => $result['final_amount'],
        ], 'كود الخصم صالح');
    }

    /**
     * Promotions applicable to a specific venue.
     */
    public function forVenue(string $slug): JsonResponse
    {
        $venue = Venue::where('slug', $slug)->firstOrFail();

        $promotions = Promotion::query()
            ->active()
            ->forVenue($venue->id)
            ->with('venue')
            ->latest()
            ->get();

        return $this->success(PromotionResource::collection($promotions));
    }

    public function qrRedeem(Request $request, QrPromotionService $service): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string|max:100',
            'booking_id' => 'sometimes|nullable|integer|exists:bookings,id',
        ]);

        try {
            $result = $service->redeem(
                (string) $request->input('qr_code'),
                $request->user(),
                $request->integer('booking_id') ?: null,
            );
        } catch (PromotionException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success($result, 'تم استبدال العرض بنجاح');
    }

    public function removeApplied(Request $request, PromotionRemovalService $service): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        try {
            $result = $service->removeFromBooking(
                (int) $request->input('booking_id'),
                $request->user(),
            );
        } catch (PromotionException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success($result, 'تمت إزالة العرض');
    }

    /**
     * Authenticated user's promo usage history.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $usages = PromoUsage::where('user_id', $request->user()->id)
            ->with(['promotion:id,code,slug,type,value', 'booking:id,booking_code,venue_id,booking_date', 'booking.venue:id,slug,name'])
            ->latest()
            ->paginate(20);

        return $this->paginated($usages);
    }
}
