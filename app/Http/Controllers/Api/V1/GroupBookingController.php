<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Services\Booking\GroupBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupBookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GroupBookingService $service,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'group_size' => 'required|integer|min:2|max:20',
            'booking_date' => 'required|date|after_or_equal:today',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'duration_minutes' => 'required|integer|min:30',
            'total_price' => 'required|integer|min:0',
            'payment_split_type' => 'required|in:full,equal,custom,individual',
            'custom_splits' => 'required_if:payment_split_type,custom|array',
            'custom_splits.*.user_id' => 'required_with:custom_splits|integer|exists:users,id',
            'custom_splits.*.amount' => 'required_with:custom_splits|integer|min:0',
        ]);

        try {
            $booking = $this->service->create($validated, auth()->user());

            return $this->success(
                $booking->load('participants.user'),
                'تم إنشاء الحجز الجماعي',
                201,
            );
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    public function invite(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1|max:19',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->captain_id !== auth()->id()) {
            return $this->error('فقط الكابتن يمكنه دعوة لاعبين', null, 403);
        }

        if (! $booking->is_group_booking) {
            return $this->error('هذا الحجز ليس جماعياً', null, 422);
        }

        $invited = $this->service->invitePlayers($booking, $validated['user_ids']);

        return $this->success([
            'invited_count' => count($invited),
            'participants' => $invited,
        ], 'تم إرسال الدعوات');
    }

    public function updatePaymentShare(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_split_type' => 'required|in:full,equal,custom,individual',
            'custom_splits' => 'required_if:payment_split_type,custom|array',
            'custom_splits.*.user_id' => 'required_with:custom_splits|integer|exists:users,id',
            'custom_splits.*.amount' => 'required_with:custom_splits|integer|min:0',
        ]);

        $booking = Booking::findOrFail($id);

        if ($booking->captain_id !== auth()->id()) {
            return $this->error('فقط الكابتن يمكنه تعديل التقسيم', null, 403);
        }

        if (! $booking->is_group_booking) {
            return $this->error('هذا الحجز ليس جماعياً', null, 422);
        }

        $splitData = $booking->calculatePaymentSplit(
            $validated['payment_split_type'],
            $validated['custom_splits'] ?? null,
        );

        $booking->update([
            'payment_split_type' => $validated['payment_split_type'],
            'payment_split_data' => $splitData,
        ]);

        return $this->success($booking->fresh('participants.user'), 'تم تحديث التقسيم');
    }
}
