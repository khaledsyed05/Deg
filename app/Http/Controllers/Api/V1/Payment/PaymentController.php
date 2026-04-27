<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use ApiResponse;

    public function methods(): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('order_column')
            ->get()
            ->map(fn (PaymentMethod $m) => [
                'id' => $m->id,
                'provider_key' => $m->provider_key,
                'flow_type' => $m->flow_type,
                'name' => $m->name,
                'order' => (int) $m->order_column,
            ])->values();

        return $this->success($methods);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->forUser($request->user()->id)
            ->with('booking')
            ->orderByDesc('id')
            ->paginate((int) ($request->integer('per_page') ?: 20));

        return PaymentResource::collection($payments);
    }

    public function receipt(Payment $payment, Request $request): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $payment->load('booking.venue.club');

        return $this->success([
            'payment_reference' => $payment->provider_reference,
            'provider' => $payment->provider?->value,
            'booking_code' => $payment->booking?->booking_code,
            'venue_name' => $payment->booking?->venue?->name,
            'amount' => (int) $payment->amount,
            'currency' => $payment->currency ?? 'SYP',
            'status' => $payment->status?->value,
            'initiated_at' => $payment->initiated_at?->toISOString(),
            'completed_at' => $payment->completed_at?->toISOString(),
            'transaction_id' => $payment->provider_transaction_id,
            'issued_at' => now()->toISOString(),
        ]);
    }
}
