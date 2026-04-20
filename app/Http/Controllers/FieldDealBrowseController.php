<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class FieldDealBrowseController
{
    public function __invoke(Field $field): JsonResponse
    {
        $deals = $field->deals()
            ->where('is_active', true)
            ->where('offer_expires_at', '>', Carbon::now())
            ->get();

        return response()->json(['data' => $deals]);
    }
}