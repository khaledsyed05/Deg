<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Last-line guarantee: every 4xx/5xx response from the api/* tree is
 * reshaped to the standard envelope:
 *   { "success": false, "message": "...", "errors": ... }
 *
 * Skipped if the response already has `success` as a boolean (it's already
 * shaped — likely from ApiResponse trait or the bootstrap exception renderer).
 */
class EnsureJsonErrorShape
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->is('api/*')) {
            return $response;
        }

        $status = $response->getStatusCode();
        if ($status < 400) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (is_array($payload) && array_key_exists('success', $payload) && $payload['success'] === false) {
                return $response;
            }

            return new JsonResponse(
                $this->shapeFromPayload($payload, $status),
                $status,
                $response->headers->all()
            );
        }

        // Non-JSON 4xx/5xx (HTML, plain text, redirects from web stack) — wrap.
        $body = (string) $response->getContent();

        return new JsonResponse([
            'success' => false,
            'message' => $this->messageForStatus($status),
            'errors' => null,
            'debug' => app()->hasDebugModeEnabled() && $body !== '' ? mb_substr($body, 0, 500) : null,
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeFromPayload(mixed $payload, int $status): array
    {
        if (is_array($payload)) {
            $message = $payload['message'] ?? $payload['error'] ?? $this->messageForStatus($status);
            $errors = $payload['errors'] ?? null;

            return [
                'success' => false,
                'message' => is_string($message) ? $message : $this->messageForStatus($status),
                'errors' => $errors,
            ];
        }

        return [
            'success' => false,
            'message' => is_string($payload) && $payload !== '' ? $payload : $this->messageForStatus($status),
            'errors' => null,
        ];
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad request',
            401 => 'Unauthenticated',
            402 => 'Payment required',
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed',
            409 => 'Conflict',
            410 => 'Gone',
            413 => 'Payload too large',
            422 => 'Validation failed',
            423 => 'Locked',
            429 => 'Too many requests',
            500 => 'Server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
            504 => 'Gateway timeout',
            default => $status >= 500 ? 'Server error' : 'Request error',
        };
    }
}
