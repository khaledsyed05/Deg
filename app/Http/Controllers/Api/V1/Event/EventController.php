<?php

namespace App\Http\Controllers\Api\V1\Event;

use App\Exceptions\Event\EventException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Event\CancelEventRegistrationRequest;
use App\Http\Requests\Api\V1\Event\RegisterForEventRequest;
use App\Http\Traits\ApiResponse;
use App\Services\Event\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponse;

    public function __construct(private EventService $eventService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'type', 'club_id', 'city_id',
            'upcoming', 'featured', 'from_date', 'to_date',
            'sort', 'sort_dir',
        ]);

        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success($this->eventService->listEvents($filters, $perPage));
    }

    public function show(int $id, Request $request): JsonResponse
    {
        return $this->success($this->eventService->getEventDetails($id, $request->user()));
    }

    public function register(int $id, RegisterForEventRequest $request): JsonResponse
    {
        try {
            $registration = $this->eventService->registerForEvent(
                $id,
                $request->user(),
                $request->integer('team_id') ?: null,
                (array) $request->input('participant_info', [])
            );
        } catch (EventException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'registration_id' => $registration->id,
            'registration_number' => $registration->registration_number,
            'status' => $registration->status,
            'amount_paid' => (float) $registration->amount_paid,
            'event_id' => $registration->event_id,
        ], 'تم التسجيل في الفعالية بنجاح', 201);
    }

    public function myRegistrations(Request $request): JsonResponse
    {
        $status = $request->string('status')->value() ?: null;
        $allowed = ['pending_payment', 'confirmed', 'cancelled', 'attended', 'no_show', 'refunded'];

        if ($status && ! in_array($status, $allowed, true)) {
            return $this->validationError(['status' => 'حالة غير صحيحة']);
        }

        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        return $this->success($this->eventService->getUserEvents($request->user(), $status, $perPage));
    }

    public function cancelRegistration(int $id, CancelEventRegistrationRequest $request): JsonResponse
    {
        try {
            $registration = $this->eventService->cancelRegistration(
                $id,
                $request->user(),
                $request->input('reason')
            );
        } catch (EventException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'registration_id' => $registration->id,
            'status' => $registration->status,
            'refunded_amount' => $registration->status === 'refunded'
                ? (float) $registration->amount_paid
                : 0,
        ], 'تم إلغاء التسجيل بنجاح');
    }

    public function participants(int $id, Request $request): JsonResponse
    {
        $perPage = min(100, max(1, $request->integer('per_page', 50)));

        return $this->success($this->eventService->getEventParticipants($id, $perPage));
    }

    public function results(int $id): JsonResponse
    {
        return $this->success($this->eventService->getEventResults($id));
    }
}
