<?php

namespace App\Http\Controllers\Api\V1\Football;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Football\RegisterMatchActivityTokenRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Football\MatchActivityToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchActivityTokensController extends Controller
{
    use ApiResponse;

    public function store(RegisterMatchActivityTokenRequest $request, int $fixtureId): JsonResponse
    {
        $token = MatchActivityToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'fixture_external_id' => (string) $fixtureId,
                'platform' => $request->string('platform')->toString(),
            ],
            [
                'push_token' => $request->string('push_token')->toString(),
                'activity_id' => $request->input('activity_id'),
                'is_active' => true,
                'last_updated_at' => now(),
            ],
        );

        return $this->success(
            ['id' => $token->id],
            __('Activity token registered'),
            201,
        );
    }

    public function destroy(Request $request, int $fixtureId): JsonResponse
    {
        MatchActivityToken::query()
            ->where('user_id', $request->user()->id)
            ->where('fixture_external_id', (string) $fixtureId)
            ->update(['is_active' => false]);

        return $this->success(null, __('Activity token deactivated'));
    }
}
