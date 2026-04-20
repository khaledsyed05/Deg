<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }
}
