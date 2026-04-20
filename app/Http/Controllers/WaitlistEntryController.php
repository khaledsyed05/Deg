<?php

namespace App\Http\Controllers;

use App\Enums\WaitlistStatus;
use App\Models\Field;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitlistEntryController
{
    public function __invoke(Request $request, Field $field): JsonResponse
    {
        $validated = $request->validate([
            'desired_date'       => ['required', 'date_format:Y-m-d'],
            'desired_start_time' => ['required', 'date_format:H:i:s'],
            'desired_end_time'   => ['required', 'date_format:H:i:s', 'after:desired_start_time'],
        ]);

        $entry = $request->user()->waitlistEntries()->create([
            'field_id'           => $field->id,
            'desired_date'       => $validated['desired_date'],
            'desired_start_time' => $validated['desired_start_time'],
            'desired_end_time'   => $validated['desired_end_time'],
            'status'             => WaitlistStatus::Waiting,
        ]);

        return response()->json(['data' => $entry], 201);
    }
}