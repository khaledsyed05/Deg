<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueBrowseController
{
    public function __invoke(): JsonResponse
    {
        $venues = Venue::where('is_active', true)
            ->get(['id', 'name', 'city', 'district', 'address', 'phone', 'is_active']);

        return response()->json(['data' => $venues]);
    }
}