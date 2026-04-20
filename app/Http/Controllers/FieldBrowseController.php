<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\JsonResponse;

class FieldBrowseController
{
    public function __invoke(): JsonResponse
    {
        $fields = Field::where('is_active', true)
            ->whereHas('venue', fn ($q) => $q->where('is_active', true))
            ->with('venue:id,name,city')
            ->get();

        return response()->json(['data' => $fields]);
    }
}