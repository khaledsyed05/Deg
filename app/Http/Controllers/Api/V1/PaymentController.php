<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\InitiatePaymentRequest;
use App\Http\Requests\Api\V1\Payment\MtnConfirmRequest;
use App\Http\Requests\Api\V1\Payment\SyriatelConfirmRequest;
use App\Http\Resources\PaymentResource;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\Payment\Gateways\MtnCashGateway;
use App\Services\Payment\Gateways\SyriatelCashGateway;
use App\Services\Payment\PaymentInitiationService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentInitiationService $paymentService,
        private MtnCashGateway $mtnGateway,
        private SyriatelCashGateway $syriatelGateway,
        private PaymentRepositoryInterface $paymentRepo,
    ) {}

    /**
     * Initiate payment
     *
     * Initiates a payment for a booking via the selected gateway.
     * For OTP gateways (MTN, Syriatel), a pending payment is created and an OTP is triggered.
     * For Webview gateways (Fatora, SamaPay), a redirect URL is returned.
     *
     * @bodyParam booking_id integer required The booking ID to pay for. Example: 123
     * @bodyParam payment_provider string required Gateway to use (mtn_cash, syriatel_cash, fatora, wallet). Example: mtn_cash
     * @bodyParam phone_number string required for OTP gateways. Phone number for OTP delivery. Example: +963944123456
     *
     * @response 201 {"success": true, "data": {"id": 456, "status": "pending"}, "flow_type": "otp", "webview_url": null}
     * @response 201 {"success": true, "data": {"id": 457, "status": "pending"}, "flow_type": "webview", "webview_url": "https://pay.fatora.io/..."}
     * @response 422 {"success": false, "errors": {"booking_id": ["الحجز مطلوب"]}}
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $result = $this->paymentService->initiate(
            bookingId: $request->booking_id,
            provider: $request->payment_provider,
            userId: $request->user()->id,
            phoneNumber: $request->phone_number,
        );

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($result->payment),
            'flow_type' => $result->flowType,
            'webview_url' => $result->redirectUrl,
        ], 201);
    }

    /**
     * Confirm MTN Cash OTP
     *
     * Submits the OTP received from MTN Cash to complete the payment.
     *
     * @bodyParam payment_id integer required The payment ID to confirm. Example: 456
     * @bodyParam otp string required The 6-digit OTP code. Example: 123456
     *
     * @response 200 {"success": true, "data": {"id": 456, "status": "completed"}}
     * @response 422 {"success": false, "message": "رمز التحقق غير صحيح"}
     */
    public function confirmMtn(MtnConfirmRequest $request): JsonResponse
    {
        $payment = $this->paymentRepo->findOrFail($request->payment_id);

        $confirmed = $this->mtnGateway->confirm($payment, $request->otp);

        if (! $confirmed) {
            return response()->json([
                'success' => false,
                'message' => __('auth.otp_invalid'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment->fresh()),
        ]);
    }

    /**
     * Confirm Syriatel Cash OTP
     *
     * Submits the OTP received from Syriatel Cash to complete the payment.
     *
     * @bodyParam payment_id integer required The payment ID to confirm. Example: 456
     * @bodyParam otp string required The 6-digit OTP code. Example: 654321
     *
     * @response 200 {"success": true, "data": {"id": 456, "status": "completed"}}
     * @response 422 {"success": false, "message": "رمز التحقق غير صحيح"}
     */
    public function confirmSyriatel(SyriatelConfirmRequest $request): JsonResponse
    {
        $payment = $this->paymentRepo->findOrFail($request->payment_id);

        $confirmed = $this->syriatelGateway->confirm($payment, $request->otp);

        if (! $confirmed) {
            return response()->json([
                'success' => false,
                'message' => __('auth.otp_invalid'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment->fresh()),
        ]);
    }
}
