<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use Illuminate\Http\JsonResponse;

class DealShowController
{
    public function __invoke(Deal $deal): JsonResponse
    {
        $deal->load('field:id,name,sport_type');

        return response()->json(['data' => $deal]);
    }
}