<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stub for B3 — see Sprint 7 Phase B3 commit.
 */
class MessageController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        return $this->error('Not yet implemented', null, 501);
    }

    public function markRead(int $id, Request $request): JsonResponse
    {
        return $this->error('Not yet implemented', null, 501);
    }
}
