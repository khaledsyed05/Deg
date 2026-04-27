<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\InitiatePaymentRequest;
use App\Http\Requests\Api\V1\Payment\SyriatelConfirmRequest;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Payment;
use App\Services\Payment\Gateways\SyriatelCashGateway;
use App\Services\Payment\PaymentInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SyriatelCashController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentInitiationService $initiationService,
        private SyriatelCashGateway $gateway,
    ) {}

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->initiationService->initiate(
            bookingId: (int) $data['booking_id'],
            provider: PaymentProvider::SyriatelCash->value,
            userId: $request->user()->id,
            phoneNumber: $data['phone_number'] ?? $request->user()->phone_number,
        );

        return $this->success([
            'payment' => new PaymentResource($result->payment),
            'flow_type' => $result->flowType,
            'webview_url' => $result->redirectUrl,
        ], __('payments.otp_sent'), 201);
    }

    public function verify(SyriatelConfirmRequest $request): JsonResponse
    {
        $payment = Payment::with('booking')->findOrFail($request->integer('payment_id'));
        abort_unless($payment->user_id === $request->user()->id, 403);

        try {
            $this->gateway->confirm($payment, (string) $request->input('otp'));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success(
            new PaymentResource($payment->fresh(['booking'])),
            __('payments.completed'),
        );
    }

    public function resend(Request $request): JsonResponse
    {
        $request->validate(['payment_id' => ['required', 'integer', 'exists:payments,id']]);
        $payment = Payment::findOrFail($request->integer('payment_id'));
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status !== PaymentStatus::Pending && $payment->status !== PaymentStatus::Processing) {
            return $this->error(__('payments.resend_not_allowed'), null, 400);
        }

        try {
            $result = $this->initiationService->initiate(
                bookingId: (int) $payment->booking_id,
                provider: PaymentProvider::SyriatelCash->value,
                userId: $payment->user_id,
                phoneNumber: $payment->user?->phone_number,
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success([
            'payment' => new PaymentResource($result->payment),
        ], __('payments.otp_resent'));
    }

    public function status(Payment $payment, Request $request): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return $this->success(new PaymentResource($payment->load('booking')));
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
