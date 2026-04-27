<?php

namespace App\Http\Controllers\Api\V1\Club;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Event;
use App\Models\EventResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyEventController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $events = Event::where('club_id', $clubId)
            ->withCount('confirmedRegistrations')
            ->latest()
            ->paginate(20);

        return $this->success([
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'description_ar' => 'sometimes|nullable|string',
            'venue_id' => 'sometimes|nullable|integer|exists:venues,id',
            'cover_image_url' => 'sometimes|nullable|url|max:500',
            'type' => 'required|in:tournament,training,social,exhibition,workshop,camp',
            'sport_type' => 'sometimes|nullable|string|max:50',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'registration_closes_at' => 'required|date|before:starts_at',
            'max_participants' => 'required|integer|min:1',
            'min_participants' => 'sometimes|integer|min:1',
            'registration_fee' => 'sometimes|numeric|min:0',
            'participant_type' => 'sometimes|in:individual,team',
            'team_size' => 'sometimes|nullable|integer|min:1',
            'rules_ar' => 'sometimes|nullable|string',
            'requirements_ar' => 'sometimes|nullable|string',
            'prize_structure' => 'sometimes|nullable|array',
            'status' => 'sometimes|in:draft,open',
        ]);

        $data['club_id'] = $clubId;
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        $event = Event::create($data);

        return $this->success($event, 'تم إنشاء الفعالية', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $event = Event::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        if (in_array($event->status, ['completed', 'cancelled'], true)) {
            return $this->error('لا يمكن تعديل فعالية منتهية أو ملغاة', null, 422);
        }

        $data = $request->validate([
            'title_ar' => 'sometimes|string|max:255',
            'description_ar' => 'sometimes|nullable|string',
            'cover_image_url' => 'sometimes|nullable|url|max:500',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date',
            'registration_closes_at' => 'sometimes|date',
            'max_participants' => 'sometimes|integer|min:1',
            'registration_fee' => 'sometimes|numeric|min:0',
            'rules_ar' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:draft,open,closed,in_progress,completed,cancelled',
            'is_featured' => 'sometimes|boolean',
        ]);

        $event->update($data);

        return $this->success($event->fresh(), 'تم تحديث الفعالية');
    }

    public function publishResults(int $id, Request $request): JsonResponse
    {
        $clubId = (int) $request->attributes->get('club_id');
        $event = Event::where('id', $id)->where('club_id', $clubId)->firstOrFail();

        $data = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.registration_id' => 'sometimes|nullable|integer',
            'results.*.user_id' => 'sometimes|nullable|integer|exists:users,id',
            'results.*.team_id' => 'sometimes|nullable|integer|exists:teams,id',
            'results.*.rank' => 'required|integer|min:1',
            'results.*.stats' => 'sometimes|nullable|array',
            'results.*.prize_amount' => 'sometimes|nullable|numeric|min:0',
            'results.*.prize_description' => 'sometimes|nullable|string|max:255',
        ]);

        DB::transaction(function () use ($event, $data) {
            EventResult::where('event_id', $event->id)->delete();

            foreach ($data['results'] as $r) {
                EventResult::create(array_merge($r, ['event_id' => $event->id]));
            }

            $event->update(['status' => 'completed']);
        });

        return $this->success(['event_id' => $event->id, 'results_count' => count($data['results'])], 'تم نشر النتائج');
    }
}
