<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\InitiatePaymentRequest;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Payment;
use App\Services\Payment\PaymentInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile "bank transfer" flow. Backed by the existing Fatora gateway (the
 * current bank-card webview provider). AlBaraka-native gateway isn't wired yet.
 */
class BankTransferController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentInitiationService $initiationService,
    ) {}

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->initiationService->initiate(
            bookingId: (int) $data['booking_id'],
            provider: PaymentProvider::Fatora->value,
            userId: $request->user()->id,
            phoneNumber: null,
        );

        return $this->success([
            'payment' => new PaymentResource($result->payment),
            'flow_type' => $result->flowType,
            'webview_url' => $result->redirectUrl,
        ], __('payments.bank_redirect'), 201);
    }

    public function view(Request $request): JsonResponse
    {
        $request->validate(['payment_id' => ['required', 'integer', 'exists:payments,id']]);
        $payment = Payment::findOrFail($request->integer('payment_id'));
        abort_unless($payment->user_id === $request->user()->id, 403);

        return $this->success([
            'webview_url' => data_get($payment->provider_meta, 'redirect_url'),
            'payment' => new PaymentResource($payment),
        ]);
    }

    public function success(Request $request): JsonResponse
    {
        return $this->success(null, __('payments.bank_success'));
    }

    public function cancel(Payment $payment, Request $request): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
            return $this->error(__('payments.cancel_not_allowed'), null, 400);
        }

        $payment->update(['status' => PaymentStatus::Cancelled]);

        return $this->success(new PaymentResource($payment->fresh()), __('payments.cancelled'));
    }
}
