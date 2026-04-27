<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ClubUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'content' => 'sometimes|nullable|string',
            'content_ar' => 'sometimes|nullable|string',
            'type' => 'sometimes|in:announcement,promotion,event,news,venue_update,maintenance',
            'image_url' => 'sometimes|nullable|url|max:500',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
            'related_event_id' => 'sometimes|nullable|integer|exists:events,id',
            'related_promotion_id' => 'sometimes|nullable|integer|exists:promotions,id',
        ]);

        $data['club_id'] = $clubId;
        $data['type'] = $data['type'] ?? 'announcement';
        $data['is_published'] = $data['is_published'] ?? true;
        $data['published_at'] = $data['published_at'] ?? now();

        $update = ClubUpdate::create($data);

        return $this->success($update, 'تم نشر التحديث', 201);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $update = ClubUpdate::where('id', $id)->where('club_id', $clubId)->firstOrFail();
        $update->delete();

        return $this->success([], 'تم حذف التحديث');
    }
}
