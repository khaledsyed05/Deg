<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Venue\CategoryResource;
use App\Http\Traits\ApiResponse;
use App\Models\VenueCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = VenueCategory::query()
            ->where('is_active', true)
            ->withCount('venues')
            ->orderBy('order_column')
            ->get();

        return $this->success(CategoryResource::collection($categories));
    }

    public function show(VenueCategory $category): JsonResponse
    {
        $category->loadCount('venues');

        return $this->success(new CategoryResource($category));
    }
}
