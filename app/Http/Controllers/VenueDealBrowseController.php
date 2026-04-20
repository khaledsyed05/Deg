<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class VenueDealBrowseController
{
    public function __invoke(Venue $venue): JsonResponse
    {
        $deals = Deal::whereHas('field', fn ($q) => $q->where('venue_id', $venue->id))
            ->where('is_active', true)
            ->where('offer_expires_at', '>', Carbon::now())
            ->with('field:id,name,sport_type')
            ->get();

        return response()->json(['data' => $deals]);
    }
}