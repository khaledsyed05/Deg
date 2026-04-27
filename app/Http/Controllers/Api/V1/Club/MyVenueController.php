<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyVenueController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venues = Venue::where('club_id', $clubId)
            ->with(['category'])
            ->withCount(['bookings', 'reviews'])
            ->paginate(20);

        return $this->success([
            'data' => $venues->items(),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'total' => $venues->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');

        $data = $request->validate([
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255',
            'name.en' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|array',
            'category_id' => 'required|integer|exists:venue_categories,id',
            'address' => 'sometimes|nullable|string|max:500',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'capacity' => 'sometimes|integer|min:1',
            'price_per_hour' => 'sometimes|integer|min:0',
            'amenities' => 'sometimes|array',
            'opening_hours' => 'sometimes|array',
        ]);

        $data['club_id'] = $clubId;
        $data['status'] = 'inactive';

        $venue = Venue::create($data);

        return $this->success($venue, 'تم إنشاء الملعب (بانتظار الموافقة)', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $data = $request->validate([
            'name' => 'sometimes|array',
            'description' => 'sometimes|nullable|array',
            'category_id' => 'sometimes|integer|exists:venue_categories,id',
            'address' => 'sometimes|nullable|string|max:500',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'capacity' => 'sometimes|integer|min:1',
            'price_per_hour' => 'sometimes|integer|min:0',
            'amenities' => 'sometimes|array',
            'opening_hours' => 'sometimes|array',
        ]);

        $venue->update($data);

        return $this->success($venue->fresh(), 'تم تحديث الملعب');
    }

    public function uploadPhotos(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $uploaded = [];
        foreach ($request->file('photos', []) as $photo) {
            $media = $venue->addMedia($photo->getRealPath())
                ->usingFileName($photo->hashName())
                ->toMediaCollection('photos');
            $uploaded[] = ['id' => $media->id, 'url' => $media->getUrl()];
        }

        return $this->success(['venue_id' => $venue->id, 'photos' => $uploaded], 'تم رفع الصور', 201);
    }

    public function deletePhoto(int $id, int $photoId, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $venue = Venue::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $media = $venue->getMedia('photos')->where('id', $photoId)->first();

        if (! $media) {
            return $this->error('الصورة غير موجودة', null, 404);
        }

        $media->delete();

        return $this->success([], 'تم حذف الصورة');
    }
}
