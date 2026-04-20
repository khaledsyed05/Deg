<?php

namespace App\Http\Controllers;

use App\Models\Field;
use Illuminate\Http\JsonResponse;

class FieldShowController
{
    public function __invoke(Field $field): JsonResponse
    {
        $field->load('venue:id,name,city,district,address,phone');

        return response()->json(['data' => $field]);
    }
}