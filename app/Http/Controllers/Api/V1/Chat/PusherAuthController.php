<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stub for B4 — see Sprint 7 Phase B4 commit.
 */
class PusherAuthController extends Controller
{
    use ApiResponse;

    public function auth(Request $request): JsonResponse
    {
        return $this->error('Not yet implemented', null, 501);
    }
}
