<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Chat\ChannelAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint Pusher's client SDK calls when subscribing to a private
 * channel. Returns the signed auth array that Pusher's edge servers
 * verify, or 403 when the caller has no business reading that
 * channel's traffic.
 *
 * The actual security logic lives in {@see ChannelAuthorizer} —
 * this controller just validates the request shape.
 */
class PusherAuthController extends Controller
{
    use ApiResponse;

    public function auth(Request $request, ChannelAuthorizer $authorizer): JsonResponse
    {
        $data = $request->validate([
            'socket_id' => ['required', 'string', 'max:200'],
            'channel_name' => ['required', 'string', 'max:200'],
        ]);

        $signed = $authorizer->authorize(
            $request->user(),
            $data['channel_name'],
            $data['socket_id'],
        );

        if ($signed === null) {
            return $this->forbidden();
        }

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => $signed,
            'errors' => null,
        ]);
    }
}
