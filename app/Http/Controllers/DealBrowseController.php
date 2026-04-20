<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DealBrowseController
{
    public function __invoke(): JsonResponse
    {
        $deals = Deal::where('is_active', true)
            ->where('offer_expires_at', '>', Carbon::now())
            ->with('field:id,name,sport_type')
            ->get();

        return response()->json(['data' => $deals]);
    }
}