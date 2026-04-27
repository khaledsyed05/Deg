<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentWebhookService;
use App\Services\Wallet\Gateways\BankWalletGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Unified bank-callback endpoint. Routes the payload to either the booking
 * webhook service or the wallet top-up service depending on payment_type.
 */
class BankCallbackController extends Controller
{
    public function __construct(
        private BankWalletGateway $bankGateway,
        private WalletService $walletService,
        private PaymentWebhookService $bookingWebhookService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::channel('payments')->info('bank.callback.received', ['ip' => $request->ip(), 'payload' => $payload]);

        if (! $this->bankGateway->verifyCallbackSignature($payload)) {
            Log::channel('payments')->warning('bank.callback.invalid_signature', ['ip' => $request->ip()]);

            return response()->json(['responseCode' => 'KO', 'message' => 'invalid_signature'], 401);
        }

        $reference = $payload['idTransaction'] ?? $payload['transactionReference'] ?? null;
        if (! $reference) {
            return response()->json(['responseCode' => 'KO', 'message' => 'missing_reference'], 400);
        }

        $payment = Payment::where('provider_reference', $reference)->first();
        if (! $payment) {
            Log::channel('payments')->warning('bank.callback.unknown_payment', ['reference' => $reference]);

            return response()->json(['responseCode' => 'KO', 'message' => 'unknown_payment'], 404);
        }

        try {
            if ($payment->isWalletTopup()) {
                $this->walletService->handleBankCallback($payload);
            } else {
                // Reuse existing booking-payment webhook pipeline.
                $normalized = [
                    'status' => strtoupper((string) ($payload['transactionStat'] ?? '')) === 'CAPTURED' ? 'completed' : 'failed',
                    'transaction_id' => (string) $reference,
                    'amount' => (float) ($payload['amount'] ?? $payment->amount),
                    'reference' => (string) $reference,
                ];
                $this->bookingWebhookService->process($normalized);
            }

            return response()->json(['responseCode' => 'OK']);
        } catch (\Throwable $e) {
            Log::channel('payments')->error('bank.callback.failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return response()->json(['responseCode' => 'KO', 'message' => 'processing_error'], 500);
        }
    }
}
