<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateCurrentUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', Rule::unique('users', 'phone')->ignore($request->user()->id)],
        ]);

        $request->user()->update($validated);

        return response()->json(['data' => $request->user()->fresh()]);
    }
}
