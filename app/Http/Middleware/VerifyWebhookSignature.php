<?php

namespace App\Http\Middleware;

use App\Support\WebhookSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies a webhook's HMAC-SHA256 signature against
 * `services.{provider}.webhook_secret`.
 *
 * Configurable per provider. The signature header defaults to
 * `X-Signature` but each provider can override via
 * `services.{provider}.webhook_header`.
 *
 * If `services.{provider}.verify_signatures` is false (default in
 * dev / sandbox), the middleware passes through — useful while a
 * payment provider's sandbox doesn't sign yet. Production must
 * flip `PAYMENT_VERIFY_SIGNATURES=true` in env.
 *
 * Usage in routes:
 *   Route::post('webhooks/syriatel/callback', [...])
 *     ->middleware('verify.webhook:syriatel');
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        if (! config("services.{$provider}.verify_signatures", false)) {
            return $next($request);
        }

        $secret = (string) config("services.{$provider}.webhook_secret", '');
        $header = (string) config("services.{$provider}.webhook_header", 'X-Signature');

        if ($secret === '') {
            return response()->json([
                'success' => false,
                'message' => "Webhook secret for {$provider} is not configured.",
                'data' => null,
                'errors' => null,
            ], 401);
        }

        $providedSignature = (string) $request->header($header, '');
        $payload = (string) $request->getContent();

        if (! WebhookSignature::verify($payload, $providedSignature, $secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
