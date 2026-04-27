<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\ManualType;
use App\Enums\RemainingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $locale = app()->getLocale();

        $filters = [
            'search' => $request->string('search')->toString(),
            'venue_slug' => $request->string('venue_slug')->toString(),
            'status' => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $activeTab = $request->string('tab')->toString() ?: 'all';

        $venueIds = $club->venues()->pluck('id');

        $activeStatuses = [
            BookingStatus::Confirmed->value,
            BookingStatus::Scheduled->value,
            BookingStatus::Completed->value,
        ];

        $todayBookings = Booking::whereIn('venue_id', $venueIds)
            ->whereDate('booking_date', today())
            ->whereIn('status', $activeStatuses)
            ->count();

        $upcomingBookings = Booking::whereIn('venue_id', $venueIds)
            ->where('booking_date', '>=', today())
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value])
            ->count();

        $monthRevenue = (int) Booking::whereIn('venue_id', $venueIds)
            ->whereBetween('booking_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', BookingStatus::Completed->value)
            ->sum('total_price');

        $pendingPayments = Booking::whereIn('venue_id', $venueIds)
            ->where('deposit_status', DepositStatus::None->value)
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value])
            ->count();

        $query = Booking::query()
            ->with(['venue:id,slug,name', 'user:id,name,phone_number'])
            ->whereIn('venue_id', $venueIds);

        match ($activeTab) {
            'today' => $query->whereDate('booking_date', today()),
            'upcoming' => $query->where('booking_date', '>=', today())
                ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value]),
            'past' => $query->where('booking_date', '<', today())
                ->where('status', BookingStatus::Completed->value),
            'cancelled' => $query->where('status', BookingStatus::Cancelled->value),
            default => null,
        };

        if ($search = $filters['search']) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($venueSlug = $filters['venue_slug']) {
            $query->whereHas('venue', fn (Builder $q) => $q->where('slug', $venueSlug));
        }

        if ($status = $filters['status']) {
            $query->where('status', $status);
        }

        if ($dateFrom = $filters['date_from']) {
            $query->whereDate('booking_date', '>=', $dateFrom);
        }

        if ($dateTo = $filters['date_to']) {
            $query->whereDate('booking_date', '<=', $dateTo);
        }

        $bookings = $query
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'start_time' => substr((string) $b->start_time, 0, 5),
                'end_time' => substr((string) $b->end_time, 0, 5),
                'duration_minutes' => (int) $b->duration_minutes,
                'venue' => [
                    'slug' => $b->venue?->slug,
                    'name' => $b->venue?->getTranslation('name', $locale) ?: $b->venue?->getTranslation('name', 'ar'),
                ],
                'player' => [
                    'name' => $b->user?->name ?? '—',
                    'phone' => $b->user?->phone_number,
                ],
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'deposit_status' => $b->deposit_status instanceof DepositStatus ? $b->deposit_status->value : $b->deposit_status,
                'total_price' => (int) $b->total_price,
                'can_cancel' => in_array(
                    $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                    [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value],
                    true,
                ),
                'can_complete' => $b->booking_date?->isToday()
                    && ($b->status instanceof BookingStatus ? $b->status->value : $b->status) === BookingStatus::Confirmed->value,
                'can_mark_no_show' => $b->booking_date?->isToday()
                    && ($b->status instanceof BookingStatus ? $b->status->value : $b->status) === BookingStatus::Confirmed->value,
            ]);

        return Inertia::render('Club/Bookings/Index', [
            'stats' => [
                'today_bookings' => $todayBookings,
                'upcoming_bookings' => $upcomingBookings,
                'month_revenue' => $monthRevenue,
                'pending_payments' => $pendingPayments,
            ],
            'bookings' => $bookings,
            'filters' => $filters,
            'activeTab' => $activeTab,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $locale = app()->getLocale();

        $venues = $club->venues()
            ->where('status', 'active')
            ->with('category:id,name')
            ->orderBy('name->ar')
            ->get(['id', 'slug', 'name', 'category_id', 'capacity', 'price_from'])
            ->map(fn (Venue $v) => [
                'slug' => $v->slug,
                'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                'category' => $v->category?->getTranslation('name', $locale)
                    ?: $v->category?->getTranslation('name', 'ar')
                    ?: '—',
                'capacity' => $v->capacity,
                'price_from' => (int) ($v->price_from ?? 0),
            ]);

        return Inertia::render('Club/Bookings/Create', [
            'venues' => $venues,
        ]);
    }

    public function searchPlayers(Request $request): JsonResponse|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $search = trim((string) $request->string('search'));
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $venueIds = $club->venues()->pluck('id');

        $players = User::query()
            ->whereHas('bookings', fn (Builder $q) => $q->whereIn('venue_id', $venueIds))
            ->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            })
            ->withCount([
                'bookings as total_bookings' => fn (Builder $q) => $q->whereIn('venue_id', $venueIds),
            ])
            ->limit(10)
            ->get(['id', 'name', 'phone_number', 'email']);

        return response()->json($players);
    }

    public function checkAvailability(Request $request): JsonResponse|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'venue_slug' => ['required', 'exists:venues,slug'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:300'],
        ]);

        $venue = Venue::where('slug', $validated['venue_slug'])->firstOrFail();
        if ((int) $venue->club_id !== (int) $club->id) {
            abort(403);
        }

        $startTime = $validated['start_time'];
        $endTime = Carbon::createFromFormat('H:i', $startTime)
            ->addMinutes((int) $validated['duration_minutes'])
            ->format('H:i');

        $conflict = Booking::where('venue_id', $venue->id)
            ->where('booking_date', $validated['date'])
            ->whereIn('status', [
                BookingStatus::Confirmed->value,
                BookingStatus::Scheduled->value,
            ])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        return response()->json([
            'available' => ! $conflict,
            'end_time' => $endTime,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'player_id' => ['nullable', 'required_without:new_player.phone_number', 'integer', 'exists:users,id'],
            'new_player.name' => ['required_without:player_id', 'string', 'max:255'],
            'new_player.phone_number' => ['required_without:player_id', 'string', 'max:20'],
            'new_player.email' => ['nullable', 'email', 'max:255'],

            'venue_slug' => ['required', 'exists:venues,slug'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:300'],

            'payment_method' => ['required', 'in:cash,card,bank_transfer,pay_later'],
            'payment_type' => ['required', 'in:full,partial'],
            'deposit_amount' => ['required_if:payment_type,partial', 'nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $venue = Venue::where('slug', $validated['venue_slug'])->firstOrFail();
        if ((int) $venue->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للحجز على هذا الملعب.');
        }

        // Resolve player
        if (! empty($validated['player_id'])) {
            $playerId = (int) $validated['player_id'];
        } else {
            $existing = User::where('phone_number', $validated['new_player']['phone_number'])->first();
            if ($existing) {
                $playerId = $existing->id;
            } else {
                $player = User::create([
                    'name' => $validated['new_player']['name'],
                    'phone_number' => $validated['new_player']['phone_number'],
                    'email' => $validated['new_player']['email'] ?? null,
                    'password' => Hash::make(Str::random(24)),
                    'role' => UserRole::Player->value,
                ]);
                $playerId = $player->id;
            }
        }

        // Time + price
        $start = Carbon::createFromFormat('H:i', $validated['start_time']);
        $durationMinutes = (int) $validated['duration_minutes'];
        $end = $start->copy()->addMinutes($durationMinutes);
        $startsAt = Carbon::parse($validated['booking_date'].' '.$start->format('H:i:s'));
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        $hourRate = (int) ($venue->price_from ?? 0);
        $totalPrice = (int) round($hourRate * ($durationMinutes / 60));

        // Conflict guard
        $conflict = Booking::where('venue_id', $venue->id)
            ->where('booking_date', $validated['booking_date'])
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value])
            ->where('start_time', '<', $end->format('H:i'))
            ->where('end_time', '>', $start->format('H:i'))
            ->exists();

        if ($conflict) {
            return back()
                ->withInput()
                ->with('flash_key', 'clubVenueNotAvailable')
                ->with('flash_type', 'error');
        }

        // Payment resolution
        $depositAmount = 0;
        $depositStatus = DepositStatus::None->value;
        $remainingStatus = RemainingStatus::None->value;

        if ($validated['payment_method'] !== 'pay_later') {
            if ($validated['payment_type'] === 'full') {
                $depositAmount = $totalPrice;
                $depositStatus = DepositStatus::Paid->value;
                $remainingStatus = RemainingStatus::None->value;
            } else {
                $depositAmount = min((int) $validated['deposit_amount'], $totalPrice);
                $depositStatus = DepositStatus::Paid->value;
                $remainingStatus = RemainingStatus::DueOnArrival->value;
            }
        } else {
            $remainingStatus = RemainingStatus::DueOnArrival->value;
        }

        $remainingAmount = max(0, $totalPrice - $depositAmount);

        $manualNoteParts = array_filter([
            'payment_method: '.$validated['payment_method'],
            $validated['notes'] ?? null,
        ]);

        $booking = Booking::create([
            'booking_code' => $this->generateBookingCode(),
            'user_id' => $playerId,
            'venue_id' => $venue->id,
            'source' => BookingSource::Manual->value,
            'manual_type' => ManualType::External->value,
            'manual_note' => implode(' | ', $manualNoteParts),
            'status' => BookingStatus::Confirmed->value,
            'booking_date' => $validated['booking_date'],
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $durationMinutes,
            'venue_price' => $totalPrice,
            'total_price' => $totalPrice,
            'club_payout_amount' => $totalPrice,
            'currency' => 'SYP',
            'deposit_amount' => $depositAmount,
            'deposit_status' => $depositStatus,
            'remaining_amount' => $remainingAmount,
            'remaining_status' => $remainingStatus,
            'notes' => $validated['notes'] ?? null,
        ]);

        activity()
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->withProperties([
                'payment_method' => $validated['payment_method'],
                'payment_type' => $validated['payment_type'],
                'deposit_amount' => $depositAmount,
            ])
            ->log('club_manual_booking_created');

        return redirect()
            ->route('club.bookings.show', $booking->id)
            ->with('flash_key', 'manualBookingCreated')
            ->with('flash_type', 'success');
    }

    private function generateBookingCode(): string
    {
        return Booking::generateBookingCode();
    }

    public function calendar(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $view = in_array($request->string('view')->toString(), ['month', 'week', 'day'], true)
            ? $request->string('view')->toString()
            : 'month';

        $dateInput = $request->string('date')->toString();
        try {
            $currentDate = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        } catch (\Throwable) {
            $currentDate = Carbon::today();
        }

        $venueSlug = $request->string('venue_slug')->toString() ?: null;

        [$startDate, $endDate] = match ($view) {
            'week' => [
                $currentDate->copy()->startOfWeek(Carbon::SATURDAY),
                $currentDate->copy()->endOfWeek(Carbon::FRIDAY),
            ],
            'day' => [
                $currentDate->copy()->startOfDay(),
                $currentDate->copy()->endOfDay(),
            ],
            default => [
                $currentDate->copy()->startOfMonth()->startOfWeek(Carbon::SATURDAY),
                $currentDate->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY),
            ],
        };

        $locale = app()->getLocale();
        $venueIds = $club->venues()->pluck('id');

        $query = Booking::query()
            ->with(['venue:id,slug,name', 'user:id,name,phone_number'])
            ->whereIn('venue_id', $venueIds)
            ->whereBetween('booking_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($venueSlug) {
            $query->whereHas('venue', fn (Builder $q) => $q->where('slug', $venueSlug));
        }

        $bookings = $query
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'start_time' => substr((string) $b->start_time, 0, 5),
                'end_time' => substr((string) $b->end_time, 0, 5),
                'duration_minutes' => (int) $b->duration_minutes,
                'venue' => [
                    'slug' => $b->venue?->slug,
                    'name' => $b->venue?->getTranslation('name', $locale)
                        ?: $b->venue?->getTranslation('name', 'ar'),
                ],
                'player' => [
                    'name' => $b->user?->name ?? '—',
                    'phone' => $b->user?->phone_number,
                ],
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'total_price' => (int) $b->total_price,
            ]);

        $bookingsByDate = $bookings->groupBy('date')->map->values();

        return Inertia::render('Club/Bookings/Calendar', [
            'view' => $view,
            'currentDate' => $currentDate->toDateString(),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'bookingsByDate' => $bookingsByDate,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
            'selectedVenue' => $venueSlug,
        ]);
    }

    public function show(Booking $booking): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($booking, $club);

        $booking->load([
            'user:id,name,phone_number,email',
            'venue:id,slug,name,category_id,amenities,latitude,longitude',
            'venue.category:id,name',
            'payments' => fn ($q) => $q->latest(),
        ]);

        $locale = app()->getLocale();

        $playerTotal = Booking::whereIn(
            'venue_id',
            $club->venues()->pluck('id'),
        )
            ->where('user_id', $booking->user_id)
            ->whereIn('status', [BookingStatus::Completed->value, BookingStatus::Confirmed->value])
            ->count();

        $lastBookingDate = Booking::whereIn(
            'venue_id',
            $club->venues()->pluck('id'),
        )
            ->where('user_id', $booking->user_id)
            ->where('id', '!=', $booking->id)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->value('booking_date');

        $status = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        $depositStatus = $booking->deposit_status instanceof DepositStatus
            ? $booking->deposit_status->value
            : $booking->deposit_status;

        $latestCompletedPayment = $booking->payments->firstWhere('status', 'completed')
            ?? $booking->payments->first();

        $timeline = $this->buildTimeline($booking, $status);

        $venueName = $booking->venue?->getTranslation('name', $locale)
            ?: $booking->venue?->getTranslation('name', 'ar');
        $categoryName = $booking->venue?->category?->getTranslation('name', $locale)
            ?: $booking->venue?->category?->getTranslation('name', 'ar');

        $remainingAmount = (int) ($booking->remaining_amount
            ?? max(0, (int) $booking->total_price - (int) $booking->deposit_amount));

        return Inertia::render('Club/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'date' => $booking->booking_date?->toDateString(),
                'start_time' => substr((string) $booking->start_time, 0, 5),
                'end_time' => substr((string) $booking->end_time, 0, 5),
                'duration_minutes' => (int) $booking->duration_minutes,
                'duration_hours' => round(((int) $booking->duration_minutes) / 60, 1),
                'status' => $status,
                'deposit_status' => $depositStatus,
                'total_price' => (int) $booking->total_price,
                'deposit_amount' => (int) $booking->deposit_amount,
                'remaining_amount' => $remainingAmount,
                'currency' => $booking->currency ?? 'SYP',
                'notes' => $booking->notes,
                'cancellation_reason' => $booking->cancellation_reason,
                'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
                'created_at' => $booking->created_at?->toIso8601String(),
                'updated_at' => $booking->updated_at?->toIso8601String(),
                'player' => [
                    'name' => $booking->user?->name ?? '—',
                    'phone' => $booking->user?->phone_number,
                    'email' => $booking->user?->email,
                    'stats' => [
                        'total_bookings' => $playerTotal,
                        'last_booking' => $lastBookingDate?->toDateString(),
                    ],
                ],
                'venue' => [
                    'slug' => $booking->venue?->slug,
                    'name' => $venueName,
                    'category' => $categoryName ?: '—',
                    'capacity' => $booking->venue?->capacity,
                    'amenities' => $booking->venue?->amenities ?? [],
                ],
                'payment' => [
                    'provider' => $latestCompletedPayment?->provider,
                    'transaction_id' => $latestCompletedPayment?->provider_transaction_id,
                ],
                'can_cancel' => in_array($status, [
                    BookingStatus::Confirmed->value,
                    BookingStatus::Scheduled->value,
                ], true),
                'can_complete' => $booking->booking_date?->isToday()
                    && $status === BookingStatus::Confirmed->value,
                'can_mark_no_show' => $booking->booking_date?->isToday()
                    && $status === BookingStatus::Confirmed->value,
            ],
            'timeline' => $timeline,
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($booking, $club);

        $status = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        if (! in_array($status, [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value], true)) {
            return back()
                ->with('flash_key', 'cannotCancelBooking')
                ->with('flash_type', 'error');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:player_request,venue_unavailable,weather,other'],
            'notes' => ['nullable', 'string', 'max:500'],
            'refund' => ['nullable', 'boolean'],
        ]);

        $reasonText = $validated['reason'];
        if ($validated['reason'] === 'other' && ! empty($validated['notes'])) {
            $reasonText .= ': '.$validated['notes'];
        }

        $booking->update([
            'status' => BookingStatus::Cancelled->value,
            'cancellation_reason' => $reasonText,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        if (! empty($validated['refund'])) {
            activity()
                ->performedOn($booking)
                ->causedBy(Auth::user())
                ->log('club_booking_refund_requested');
        }

        activity()
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->withProperties(['reason' => $validated['reason']])
            ->log('club_booking_cancelled');

        return back()
            ->with('flash_key', 'bookingCancelled')
            ->with('flash_type', 'success');
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($booking, $club);

        $status = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        if ($status !== BookingStatus::Confirmed->value) {
            return back()
                ->with('flash_key', 'cannotCompleteBooking')
                ->with('flash_type', 'error');
        }

        $validated = $request->validate([
            'remaining_paid' => ['nullable', 'boolean'],
            'request_rating' => ['nullable', 'boolean'],
        ]);

        $booking->update(['status' => BookingStatus::Completed->value]);

        if (! empty($validated['remaining_paid'])) {
            $booking->update([
                'remaining_status' => 'confirmed',
                'remaining_confirmed_at' => now(),
                'remaining_confirmed_by' => Auth::id(),
            ]);
        }

        activity()
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->withProperties([
                'remaining_paid' => (bool) ($validated['remaining_paid'] ?? false),
                'request_rating' => (bool) ($validated['request_rating'] ?? false),
            ])
            ->log('club_booking_completed');

        return back()
            ->with('flash_key', 'bookingCompleted')
            ->with('flash_type', 'success');
    }

    public function markNoShow(Booking $booking): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureOwnership($booking, $club);

        $status = $booking->status instanceof BookingStatus ? $booking->status->value : $booking->status;
        if ($status !== BookingStatus::Confirmed->value) {
            return back()
                ->with('flash_key', 'cannotMarkNoShow')
                ->with('flash_type', 'error');
        }

        $booking->update(['status' => BookingStatus::NoShow->value]);

        activity()
            ->performedOn($booking)
            ->causedBy(Auth::user())
            ->log('club_booking_no_show');

        return back()
            ->with('flash_key', 'bookingMarkedNoShow')
            ->with('flash_type', 'success');
    }

    /**
     * @return array<int, array{status:string,label_key:string,timestamp:?string,completed:bool}>
     */
    private function buildTimeline(Booking $booking, string $status): array
    {
        $created = [
            'status' => 'created',
            'label_key' => 'clubTimelineCreated',
            'timestamp' => $booking->created_at?->toIso8601String(),
            'completed' => true,
        ];

        if ($status === BookingStatus::Cancelled->value) {
            return [
                $created,
                [
                    'status' => 'cancelled',
                    'label_key' => 'clubTimelineCancelled',
                    'timestamp' => ($booking->cancelled_at ?? $booking->updated_at)?->toIso8601String(),
                    'completed' => true,
                ],
            ];
        }

        $confirmedReached = in_array($status, [
            BookingStatus::Confirmed->value,
            BookingStatus::Completed->value,
            BookingStatus::NoShow->value,
        ], true);

        $terminalReached = in_array($status, [
            BookingStatus::Completed->value,
            BookingStatus::NoShow->value,
        ], true);

        return [
            $created,
            [
                'status' => 'confirmed',
                'label_key' => 'clubTimelineConfirmed',
                'timestamp' => $confirmedReached ? $booking->created_at?->toIso8601String() : null,
                'completed' => $confirmedReached,
            ],
            [
                'status' => $status === BookingStatus::NoShow->value ? 'no_show' : 'completed',
                'label_key' => $status === BookingStatus::NoShow->value
                    ? 'clubTimelineNoShow'
                    : 'clubTimelineCompleted',
                'timestamp' => $terminalReached ? $booking->updated_at?->toIso8601String() : null,
                'completed' => $terminalReached,
            ],
        ];
    }

    private function ensureOwnership(Booking $booking, Club $club): void
    {
        $venueClubId = $booking->venue?->club_id
            ?? Venue::whereKey($booking->venue_id)->value('club_id');

        if ((int) $venueClubId !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذا الحجز.');
        }
    }

    private function resolveClub(): Club|RedirectResponse
    {
        $user = Auth::user();

        $club = Club::where('owner_id', $user->id)->first()
            ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.dashboard')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        return $club;
    }
}
