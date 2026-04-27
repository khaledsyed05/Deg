<?php

namespace App\Services\Event;

use App\Enums\CreditType;
use App\Exceptions\Event\EventException;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\Event\EventRegistrationCancelledNotification;
use App\Notifications\Event\EventRegistrationConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listEvents(array $filters = [], int $perPage = 15): array
    {
        $query = Event::published()
            ->with(['club:id,name,slug', 'venue:id,name,slug']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['club_id'])) {
            $query->where('club_id', $filters['club_id']);
        }

        if (! empty($filters['city_id'])) {
            $query->whereHas('club', fn ($q) => $q->where('city_id', $filters['city_id']));
        }

        if (! empty($filters['upcoming'])) {
            $query->upcoming();
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }

        if (! empty($filters['from_date'])) {
            $query->where('starts_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('starts_at', '<=', $filters['to_date']);
        }

        $sortBy = $filters['sort'] ?? 'starts_at';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $allowedSorts = ['starts_at', 'created_at', 'registration_fee', 'views_count'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'starts_at';
        }
        $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');

        $events = $query->paginate($perPage);

        return [
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventDetails(int $eventId, ?User $user = null): array
    {
        $event = Event::with([
            'club:id,name,slug,phone_number',
            'venue:id,name,slug,latitude,longitude',
        ])
            ->withCount('confirmedRegistrations')
            ->findOrFail($eventId);

        $event->increment('views_count');

        $userRegistration = null;
        if ($user) {
            $userRegistration = $event->registrations()
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->first();
        }

        return [
            'id' => $event->id,
            'title' => $event->title,
            'title_ar' => $event->title_ar,
            'description' => $event->description,
            'description_ar' => $event->description_ar,
            'cover_image_url' => $event->cover_image_url,
            'gallery_urls' => $event->gallery_urls ?? [],
            'type' => $event->type,
            'sport_type' => $event->sport_type,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'registration_closes_at' => $event->registration_closes_at?->toIso8601String(),
            'max_participants' => (int) $event->max_participants,
            'current_participants' => (int) $event->current_participants,
            'remaining_spots' => $event->remaining_spots,
            'registration_fee' => (float) $event->registration_fee,
            'participant_type' => $event->participant_type,
            'team_size' => $event->team_size,
            'prize_structure' => $event->prize_structure ?? [],
            'rules' => $event->rules,
            'rules_ar' => $event->rules_ar,
            'requirements_ar' => $event->requirements_ar,
            'status' => $event->status,
            'is_registration_open' => $event->is_registration_open,
            'is_user_registered' => $userRegistration !== null,
            'user_registration_status' => $userRegistration?->status,
            'club' => $event->club ? [
                'id' => $event->club->id,
                'name' => $event->club->getTranslation('name', 'ar', false)
                    ?: $event->club->getTranslation('name', 'en', false),
                'slug' => $event->club->slug,
                'phone' => $event->club->phone_number,
            ] : null,
            'venue' => $event->venue ? [
                'id' => $event->venue->id,
                'name' => $event->venue->getTranslation('name', 'ar', false)
                    ?: $event->venue->getTranslation('name', 'en', false),
                'slug' => $event->venue->slug,
                'address' => null,
                'location' => $event->venue->latitude ? [
                    'latitude' => (float) $event->venue->latitude,
                    'longitude' => (float) $event->venue->longitude,
                ] : null,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $participantInfo
     */
    public function registerForEvent(
        int $eventId,
        User $user,
        ?int $teamId = null,
        array $participantInfo = []
    ): EventRegistration {
        $event = Event::findOrFail($eventId);
        $this->checkRegistrationEligibility($event, $user, $teamId);

        return DB::transaction(function () use ($event, $user, $teamId, $participantInfo) {
            $event = Event::whereKey($event->id)->lockForUpdate()->first();

            if ((int) $event->current_participants >= (int) $event->max_participants) {
                throw new EventException('الفعالية مكتملة العدد');
            }

            if ($event->status !== 'open') {
                throw new EventException('الفعالية غير مفتوحة للتسجيل');
            }

            $fee = (float) $event->registration_fee;
            $isFree = $fee == 0.0;

            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'amount_paid' => $fee,
                'participant_info' => $participantInfo,
                'status' => $isFree ? 'confirmed' : 'pending_payment',
                'paid_at' => $isFree ? now() : null,
            ]);

            if (! $isFree) {
                $wallet = $user->walletOrCreate();

                if ($wallet->available < $fee) {
                    throw new EventException(
                        "رصيد المحفظة غير كافٍ. الرسم: {$fee} ل.س",
                        402
                    );
                }

                $wallet->debit(
                    (int) $fee,
                    CreditType::EVENT_REGISTRATION,
                    "Event registration: {$event->title_ar}",
                    $registration,
                    [
                        'event_id' => $event->id,
                        'registration_id' => $registration->id,
                    ]
                );

                $registration->update([
                    'status' => 'confirmed',
                    'paid_at' => now(),
                ]);
            }

            $event->increment('current_participants');

            $this->safeNotify(
                $user,
                new EventRegistrationConfirmedNotification($registration->fresh())
            );

            return $registration->fresh();
        });
    }

    public function cancelRegistration(int $eventId, User $user, ?string $reason = null): EventRegistration
    {
        $registration = EventRegistration::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->firstOrFail();

        $event = $registration->event;

        $hoursUntilEvent = now()->diffInHours($event->starts_at, false);
        if ($hoursUntilEvent < 24) {
            throw new EventException('لا يمكن إلغاء التسجيل قبل 24 ساعة من بداية الفعالية');
        }

        return DB::transaction(function () use ($registration, $event, $reason, $user) {
            $registration->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            $event->decrement('current_participants');

            if ((float) $registration->amount_paid > 0) {
                $wallet = $user->walletOrCreate();
                $wallet->credit(
                    (int) $registration->amount_paid,
                    CreditType::REFUND,
                    "Event registration refund: {$event->title_ar}",
                    $registration,
                    null,
                    [
                        'event_id' => $event->id,
                        'registration_id' => $registration->id,
                    ]
                );

                $registration->update(['status' => 'refunded']);
            }

            $this->safeNotify(
                $user,
                new EventRegistrationCancelledNotification($registration->fresh())
            );

            return $registration->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserEvents(User $user, ?string $status = null, int $perPage = 20): array
    {
        $query = $user->eventRegistrations()
            ->with(['event.club:id,name,slug', 'event.venue:id,name,slug'])
            ->latest('registered_at');

        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->paginate($perPage);

        return [
            'data' => $registrations->items(),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'total' => $registrations->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventParticipants(int $eventId, int $perPage = 50): array
    {
        $event = Event::findOrFail($eventId);

        $registrations = $event->registrations()
            ->where('status', 'confirmed')
            ->with(['user:id,name,avatar_url', 'team:id,name'])
            ->orderBy('registered_at')
            ->paginate($perPage);

        return [
            'event' => [
                'id' => $event->id,
                'title_ar' => $event->title_ar,
                'current_participants' => (int) $event->current_participants,
                'max_participants' => (int) $event->max_participants,
            ],
            'participants' => $registrations->items(),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'total' => $registrations->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventResults(int $eventId): array
    {
        $event = Event::findOrFail($eventId);

        if ($event->status !== 'completed') {
            return [
                'event_id' => $event->id,
                'event_status' => $event->status,
                'message' => 'لم يتم نشر نتائج الفعالية بعد',
                'results' => [],
            ];
        }

        $results = $event->results()
            ->with(['user:id,name,avatar_url', 'team:id,name'])
            ->orderBy('rank')
            ->get();

        return [
            'event_id' => $event->id,
            'event_title_ar' => $event->title_ar,
            'event_status' => $event->status,
            'completed_at' => $event->ends_at?->toIso8601String(),
            'results' => $results,
        ];
    }

    private function checkRegistrationEligibility(Event $event, User $user, ?int $teamId = null): void
    {
        if ($event->status !== 'open') {
            throw new EventException("الفعالية غير مفتوحة للتسجيل (الحالة: {$event->status})");
        }

        if (! $event->is_registration_open) {
            throw new EventException('انتهى وقت التسجيل في الفعالية');
        }

        if ($event->isUserRegistered($user)) {
            throw new EventException('أنت مسجّل بالفعل في هذه الفعالية');
        }

        if ($event->participant_type === 'team' && ! $teamId) {
            throw new EventException('الفعالية تتطلب فريق - يرجى تحديد فريق');
        }

        if ((int) $event->current_participants >= (int) $event->max_participants) {
            throw new EventException('الفعالية مكتملة العدد');
        }
    }

    private function safeNotify(User $user, $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('event.notify_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
