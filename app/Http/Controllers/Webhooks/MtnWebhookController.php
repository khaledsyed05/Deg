<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\MtnCashGateway;
use App\Services\Payment\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MtnWebhookController extends Controller
{
    public function __construct(
        private MtnCashGateway $gateway,
        private PaymentWebhookService $webhookService,
    ) {}

    public function callback(Request $request): Response
    {
        $payload = $request->all();
        $signature = $request->header('X-MTN-Signature', '');

        if (! $this->gateway->verifyWebhookSignature($payload, $signature)) {
            Log::warning('MTN webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response('Unauthorized', 401);
        }

        $normalized = $this->gateway->processCallback($payload);

        $this->webhookService->process($normalized);

        return response('OK', 200);
    }
}
