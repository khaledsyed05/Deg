<?php

namespace App\Http\Controllers;

use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitlistEntryShowController
{
    public function __invoke(Request $request, WaitlistEntry $waitlistEntry): JsonResponse
    {
        if ($waitlistEntry->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $waitlistEntry->load('field:id,name,sport_type');

        return response()->json(['data' => $waitlistEntry]);
    }
}