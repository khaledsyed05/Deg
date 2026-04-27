<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyPromotionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');

        $promos = Promotion::where('club_id', $clubId)->latest()->paginate(20);

        return $this->success([
            'data' => $promos->items(),
            'meta' => [
                'current_page' => $promos->currentPage(),
                'last_page' => $promos->lastPage(),
                'total' => $promos->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');

        $data = $request->validate([
            'venue_id' => 'sometimes|nullable|integer|exists:venues,id',
            'code' => 'required|string|max:50|unique:promotions,code',
            'name' => 'required|array',
            'name.ar' => 'required|string',
            'description' => 'sometimes|nullable|array',
            'type' => 'required|in:percentage,fixed_amount,free_hours',
            'value' => 'required|integer|min:1',
            'min_amount' => 'sometimes|nullable|integer|min:0',
            'max_discount' => 'sometimes|nullable|integer|min:0',
            'valid_from' => 'sometimes|nullable|date',
            'valid_to' => 'sometimes|nullable|date|after:valid_from',
            'max_uses' => 'sometimes|nullable|integer|min:1',
            'max_uses_per_user' => 'sometimes|nullable|integer|min:1',
        ]);

        $data['club_id'] = $clubId;
        $data['slug'] = Str::slug($data['code']).'-'.substr(uniqid(), -4);
        $data['status'] = 'active';
        $data['applies_to'] = 'all';
        $data['created_by'] = $request->user()->id;

        $promo = Promotion::create($data);

        return $this->success($promo, 'تم إنشاء العرض', 201);
    }
}
