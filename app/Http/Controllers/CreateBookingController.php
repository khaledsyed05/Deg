<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Services\BookingCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class CreateBookingController
{
    public function __invoke(Request $request, Field $field, BookingCreationService $service): JsonResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        try {
            $booking = $service->create(
                $request->user(),
                $field,
                Carbon::parse($validated['starts_at']),
                Carbon::parse($validated['ends_at']),
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $booking], 201);
    }
}
