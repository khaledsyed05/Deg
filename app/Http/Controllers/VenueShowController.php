<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueShowController
{
    public function __invoke(Venue $venue): JsonResponse
    {
        return response()->json([
            'data' => $venue->only(['id', 'name', 'city', 'district', 'address', 'phone', 'is_active']),
        ]);
    }
}