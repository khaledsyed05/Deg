<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RemainingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cash-at-venue flow. Player commits to paying at the venue; payment stays
 * Pending until the club confirms arrival (Club dashboard complete action).
 */
class CashController extends Controller
{
    use ApiResponse;

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);
        abort_unless($booking->user_id === $request->user()->id, 403);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => (int) $booking->deposit_amount,
            'currency' => $booking->currency ?? 'SYP',
            'provider' => PaymentProvider::Cash,
            'flow_type' => PaymentFlowType::Internal,
            'status' => PaymentStatus::Pending,
            'provider_reference' => Payment::generateReference(PaymentProvider::Cash, $booking->id),
            'initiated_at' => now(),
        ]);

        $booking->update(['remaining_status' => RemainingStatus::DueOnArrival]);

        return $this->success([
            'payment' => new PaymentResource($payment),
            'instructions' => [
                'text' => __('payments.cash_instructions_body'),
                'venue' => $booking->venue?->name,
                'amount' => (int) $booking->total_price,
                'arrival' => $booking->start_time,
            ],
        ], __('payments.cash_noted'), 201);
    }

    public function instructions(): JsonResponse
    {
        return $this->success([
            'title' => __('payments.cash_instructions_title'),
            'steps' => [
                __('payments.cash_step_arrive'),
                __('payments.cash_step_pay'),
                __('payments.cash_step_receipt'),
            ],
        ]);
    }
}
