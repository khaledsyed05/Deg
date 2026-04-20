<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyBookingsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with('field:id,name,sport_type')
            ->latest()
            ->get();

        return response()->json(['data' => $bookings]);
    }
}