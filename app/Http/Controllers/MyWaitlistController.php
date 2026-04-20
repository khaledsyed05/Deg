<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyWaitlistController
{
    public function __invoke(Request $request): JsonResponse
    {
        $entries = $request->user()
            ->waitlistEntries()
            ->with('field:id,name,sport_type')
            ->latest()
            ->get();

        return response()->json(['data' => $entries]);
    }
}