<?php

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    /**
     * Emit a success envelope. Always includes the four documented keys
     * (success, message, data, errors) so that mobile clients can rely on
     * their presence regardless of payload.
     */
    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    /**
     * Emit a no-content success envelope. Use for delete-style and
     * mark-as-read responses where there's nothing to return — `data`
     * stays in the envelope as `null` per spec.
     */
    protected function noContent(?string $message = null, int $code = 200): JsonResponse
    {
        return $this->success(null, $message, $code);
    }

    /**
     * Wrap a paginator in the documented envelope, including the
     * meta.{current_page,per_page,total,last_page} block.
     *
     * If $resourceClass is provided, the items are first transformed via
     * `$resourceClass::collection($paginator)` so per-resource shaping
     * still applies. Pass `null` to emit raw items.
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        ?string $resourceClass = null,
        ?string $message = null,
        int $code = 200,
    ): JsonResponse {
        $items = $resourceClass !== null && is_subclass_of($resourceClass, JsonResource::class)
            ? $resourceClass::collection($paginator)->resolve()
            : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'errors' => null,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $code);
    }

    protected function error(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $code);
    }

    protected function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, $errors, 422);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, null, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, null, 403);
    }
}
