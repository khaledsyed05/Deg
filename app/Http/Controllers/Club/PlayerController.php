<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\PlayerNote;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'venue_slug' => $request->string('venue_slug')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'recent',
        ];

        $locale = app()->getLocale();
        $venueIds = $club->venues()->pluck('id');
        $activeSince = now()->subDays(30);
        $activeSinceISO = $activeSince->toDateString();

        // Stats
        $totalPlayers = (int) Booking::whereIn('venue_id', $venueIds)
            ->distinct('user_id')
            ->count('user_id');

        $activePlayers = (int) Booking::whereIn('venue_id', $venueIds)
            ->where('booking_date', '>=', $activeSinceISO)
            ->distinct('user_id')
            ->count('user_id');

        $lifetimeRevenue = (int) Booking::whereIn('venue_id', $venueIds)
            ->where('status', BookingStatus::Completed->value)
            ->sum('total_price');

        $totalBookingsAtClub = (int) Booking::whereIn('venue_id', $venueIds)->count();
        $avgBookings = $totalPlayers > 0
            ? round($totalBookingsAtClub / $totalPlayers, 1)
            : 0.0;

        // Player list — only users who booked at this club
        $query = User::query()
            ->whereIn('id', Booking::whereIn('venue_id', $venueIds)->select('user_id'))
            ->withCount([
                'bookings as total_bookings' => fn (Builder $q) => $q->whereIn('venue_id', $venueIds),
                'bookings as completed_bookings' => fn (Builder $q) => $q->whereIn('venue_id', $venueIds)
                    ->where('status', BookingStatus::Completed->value),
                'bookings as cancelled_bookings' => fn (Builder $q) => $q->whereIn('venue_id', $venueIds)
                    ->where('status', BookingStatus::Cancelled->value),
            ])
            ->withSum(
                ['bookings as lifetime_revenue' => fn (Builder $q) => $q->whereIn('venue_id', $venueIds)
                    ->where('status', BookingStatus::Completed->value)],
                'total_price',
            )
            ->addSelect([
                'last_booking_date' => Booking::query()
                    ->selectRaw('MAX(booking_date)')
                    ->whereColumn('bookings.user_id', 'users.id')
                    ->whereIn('venue_id', $venueIds),
            ]);

        if ($search = $filters['search']) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] === 'active') {
            $query->whereHas('bookings', fn (Builder $q) => $q->whereIn('venue_id', $venueIds)
                ->where('booking_date', '>=', $activeSinceISO));
        } elseif ($filters['status'] === 'inactive') {
            $query->whereDoesntHave('bookings', fn (Builder $q) => $q->whereIn('venue_id', $venueIds)
                ->where('booking_date', '>=', $activeSinceISO));
        }

        if ($venueSlug = $filters['venue_slug']) {
            $venueId = Venue::where('club_id', $club->id)->where('slug', $venueSlug)->value('id');
            if ($venueId) {
                $query->whereHas('bookings', fn (Builder $q) => $q->where('venue_id', $venueId));
            }
        }

        match ($filters['sort']) {
            'most_bookings' => $query->orderByDesc('total_bookings'),
            'highest_revenue' => $query->orderByDesc('lifetime_revenue'),
            default => $query->orderByDesc('last_booking_date'),
        };

        $todayISO = now()->toDateString();

        $players = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone_number' => $u->phone_number,
                'email' => $u->email,
                'total_bookings' => (int) ($u->total_bookings ?? 0),
                'completed_bookings' => (int) ($u->completed_bookings ?? 0),
                'cancelled_bookings' => (int) ($u->cancelled_bookings ?? 0),
                'lifetime_revenue' => (int) ($u->lifetime_revenue ?? 0),
                'last_booking_date' => $u->last_booking_date,
                'is_active' => $u->last_booking_date !== null
                    && $u->last_booking_date >= $activeSinceISO
                    && $u->last_booking_date <= $todayISO,
            ]);

        return Inertia::render('Club/Players/Index', [
            'stats' => [
                'total_players' => $totalPlayers,
                'active_players' => $activePlayers,
                'lifetime_revenue' => $lifetimeRevenue,
                'avg_bookings' => $avgBookings,
            ],
            'players' => $players,
            'filters' => $filters,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function show(Request $request, User $player): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $venueIds = $club->venues()->pluck('id');
        $locale = app()->getLocale();

        $playerBookingsBase = Booking::query()
            ->where('user_id', $player->id)
            ->whereIn('venue_id', $venueIds);

        if (! (clone $playerBookingsBase)->exists()) {
            abort(404, 'اللاعب غير موجود.');
        }

        $tab = in_array($request->string('tab')->toString(), ['bookings', 'notes'], true)
            ? $request->string('tab')->toString()
            : 'bookings';

        // Single-pass aggregate
        $agg = (clone $playerBookingsBase)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [BookingStatus::Completed->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled', [BookingStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show', [BookingStatus::NoShow->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN total_price ELSE 0 END), 0) as lifetime_revenue', [BookingStatus::Completed->value])
            ->selectRaw('COALESCE(AVG(CASE WHEN status = ? THEN total_price END), 0) as avg_spend', [BookingStatus::Completed->value])
            ->selectRaw('MIN(booking_date) as first_booking_date')
            ->selectRaw('MAX(booking_date) as last_booking_date')
            ->first();

        $totalBookings = (int) ($agg->total ?? 0);
        $completedBookings = (int) ($agg->completed ?? 0);
        $completionRate = $totalBookings > 0
            ? (int) round(($completedBookings / $totalBookings) * 100)
            : 0;

        $stats = [
            'total_bookings' => $totalBookings,
            'completed_bookings' => $completedBookings,
            'cancelled_bookings' => (int) ($agg->cancelled ?? 0),
            'no_show_bookings' => (int) ($agg->no_show ?? 0),
            'lifetime_revenue' => (int) ($agg->lifetime_revenue ?? 0),
            'avg_spend' => (int) round((float) ($agg->avg_spend ?? 0)),
            'completion_rate' => $completionRate,
            'first_booking_date' => $agg->first_booking_date,
            'last_booking_date' => $agg->last_booking_date,
        ];

        $filters = [
            'status' => $request->string('status')->toString(),
            'venue_slug' => $request->string('venue_slug')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $bookingsQuery = Booking::query()
            ->with(['venue:id,slug,name'])
            ->where('user_id', $player->id)
            ->whereIn('venue_id', $venueIds);

        if ($filters['status']) {
            $bookingsQuery->where('status', $filters['status']);
        }
        if ($filters['date_from']) {
            $bookingsQuery->whereDate('booking_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $bookingsQuery->whereDate('booking_date', '<=', $filters['date_to']);
        }
        if ($filters['venue_slug']) {
            $venueId = Venue::where('club_id', $club->id)
                ->where('slug', $filters['venue_slug'])
                ->value('id');
            if ($venueId) {
                $bookingsQuery->where('venue_id', $venueId);
            }
        }

        $bookings = $bookingsQuery
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date?->toDateString(),
                'venue' => [
                    'slug' => $b->venue?->slug,
                    'name' => $b->venue?->getTranslation('name', $locale)
                        ?: $b->venue?->getTranslation('name', 'ar'),
                ],
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'total_price' => (int) $b->total_price,
            ]);

        $authId = (int) Auth::id();
        $notes = PlayerNote::query()
            ->with('creator:id,name')
            ->where('club_id', $club->id)
            ->where('player_id', $player->id)
            ->latest('created_at')
            ->get()
            ->map(fn (PlayerNote $n) => [
                'id' => $n->id,
                'note' => $n->note,
                'created_by' => $n->creator?->name ?? '—',
                'created_at' => $n->created_at?->toIso8601String(),
                'can_edit' => (int) $n->created_by === $authId,
            ]);

        return Inertia::render('Club/Players/Show', [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'phone_number' => $player->phone_number,
                'email' => $player->email,
            ],
            'stats' => $stats,
            'bookings' => $bookings,
            'notes' => $notes,
            'tab' => $tab,
            'filters' => $filters,
            'venues' => $club->venues()
                ->orderBy('name->ar')
                ->get(['id', 'slug', 'name'])
                ->map(fn (Venue $v) => [
                    'slug' => $v->slug,
                    'name' => $v->getTranslation('name', $locale) ?: $v->getTranslation('name', 'ar'),
                ]),
        ]);
    }

    public function storeNote(Request $request, User $player): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $hasBookings = Booking::where('user_id', $player->id)
            ->whereIn('venue_id', $club->venues()->pluck('id'))
            ->exists();

        if (! $hasBookings) {
            abort(404);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $note = PlayerNote::create([
            'club_id' => $club->id,
            'player_id' => $player->id,
            'note' => $validated['note'],
            'created_by' => Auth::id(),
        ]);

        activity()
            ->performedOn($note)
            ->causedBy(Auth::user())
            ->log('club_player_note_created');

        return back()
            ->with('flash_key', 'noteAdded')
            ->with('flash_type', 'success');
    }

    public function updateNote(Request $request, PlayerNote $note): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureNoteOwnership($note, $club);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $note->update(['note' => $validated['note']]);

        activity()
            ->performedOn($note)
            ->causedBy(Auth::user())
            ->log('club_player_note_updated');

        return back()
            ->with('flash_key', 'noteUpdated')
            ->with('flash_type', 'success');
    }

    public function destroyNote(PlayerNote $note): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $this->ensureNoteOwnership($note, $club);

        $note->delete();

        activity()
            ->causedBy(Auth::user())
            ->log('club_player_note_deleted');

        return back()
            ->with('flash_key', 'noteDeleted')
            ->with('flash_type', 'success');
    }

    private function ensureNoteOwnership(PlayerNote $note, Club $club): void
    {
        if ((int) $note->club_id !== (int) $club->id || (int) $note->created_by !== (int) Auth::id()) {
            abort(403, 'غير مخوّل للوصول إلى هذه الملاحظة.');
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
