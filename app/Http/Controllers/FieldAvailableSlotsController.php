<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Services\AvailableSlotsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FieldAvailableSlotsController extends Controller
{
    public function __invoke(Request $request, Field $field, AvailableSlotsService $service): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
        ]);

        $date = Carbon::parse($validated['date']);
        $slotMinutes = (int) ($validated['slot_minutes'] ?? 60);

        $slots = $service->forDate($field, $date, $slotMinutes);

        return response()->json([
            'data' => array_map(fn (array $slot) => [
                'starts_at' => $slot['starts_at']->toDateTimeString(),
                'ends_at' => $slot['ends_at']->toDateTimeString(),
            ], $slots),
        ]);
    }
}
