<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueFieldBrowseController
{
    public function __invoke(Venue $venue): JsonResponse
    {
        $fields = $venue->fields()->where('is_active', true)->get();

        return response()->json(['data' => $fields]);
    }
}