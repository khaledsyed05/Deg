<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Venue\CategoryResource;
use App\Http\Traits\ApiResponse;
use App\Models\VenueCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $categories = VenueCategory::query()
            ->where('is_active', true)
            ->withCount('venues')
            ->orderBy('order_column')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(VenueCategory $category): JsonResponse
    {
        $category->loadCount('venues');

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }
}
