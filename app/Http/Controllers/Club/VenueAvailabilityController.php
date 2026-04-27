<?php

namespace App\Http\Controllers\Club;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use App\Models\Venue;
use App\Models\VenueBlockedDate;
use App\Models\VenueSpecialPricing;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VenueAvailabilityController extends Controller
{
    public function index(Request $request, Venue $venue): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }
        $this->ensureOwnership($venue, $club);

        $month = (string) $request->query('month', now()->format('Y-m'));
        try {
            $anchor = Carbon::parse($month.'-01')->startOfDay();
        } catch (\Throwable) {
            $anchor = now()->startOfMonth();
        }

        $monthStart = $anchor->copy()->startOfMonth();
        $monthEnd = $anchor->copy()->endOfMonth();

        // Pad to Saturday-start / Friday-end week
        $gridStart = $monthStart->copy();
        while ($gridStart->dayOfWeek !== Carbon::SATURDAY) {
            $gridStart->subDay();
        }
        $gridEnd = $monthEnd->copy();
        while ($gridEnd->dayOfWeek !== Carbon::FRIDAY) {
            $gridEnd->addDay();
        }

        $blockedDates = VenueBlockedDate::query()
            ->where('venue_id', $venue->id)
            ->where(function ($q) use ($gridStart, $gridEnd) {
                $q->whereBetween('start_date', [$gridStart, $gridEnd])
                    ->orWhereBetween('end_date', [$gridStart, $gridEnd])
                    ->orWhere(function ($q) use ($gridStart, $gridEnd) {
                        $q->where('start_date', '<=', $gridStart)
                            ->where('end_date', '>=', $gridEnd);
                    });
            })
            ->get();

        $specialPricing = VenueSpecialPricing::query()
            ->where('venue_id', $venue->id)
            ->where(function ($q) use ($gridStart, $gridEnd) {
                $q->whereBetween('start_date', [$gridStart, $gridEnd])
                    ->orWhereBetween('end_date', [$gridStart, $gridEnd])
                    ->orWhere(function ($q) use ($gridStart, $gridEnd) {
                        $q->where('start_date', '<=', $gridStart)
                            ->where('end_date', '>=', $gridEnd);
                    });
            })
            ->get();

        $bookingCounts = Booking::query()
            ->where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$gridStart, $gridEnd])
            ->whereIn('status', [
                BookingStatus::Confirmed->value,
                BookingStatus::Scheduled->value,
                BookingStatus::Completed->value,
            ])
            ->selectRaw('DATE(booking_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $today = today();
        $calendar = [];
        $cursor = $gridStart->copy();
        while ($cursor <= $gridEnd) {
            $dateStr = $cursor->toDateString();

            $isBlocked = $blockedDates->contains(function (VenueBlockedDate $b) use ($cursor) {
                $end = $b->end_date ?? $b->start_date;

                return $cursor->betweenIncluded($b->start_date, $end);
            });

            $special = $specialPricing->first(function (VenueSpecialPricing $sp) use ($cursor) {
                $end = $sp->end_date ?? $sp->start_date;

                return $cursor->betweenIncluded($sp->start_date, $end);
            });

            $calendar[] = [
                'date' => $dateStr,
                'day' => $cursor->day,
                'is_current_month' => $cursor->month === $anchor->month,
                'is_past' => $cursor->lt($today),
                'is_today' => $cursor->isSameDay($today),
                'is_blocked' => $isBlocked,
                'has_special_price' => (bool) $special,
                'special_price' => $special ? (int) $special->price_per_hour : null,
                'special_label' => $special?->label,
                'bookings_count' => (int) ($bookingCounts[$dateStr] ?? 0),
            ];

            $cursor->addDay();
        }

        $upcomingBlocks = VenueBlockedDate::query()
            ->where('venue_id', $venue->id)
            ->where(function ($q) use ($today) {
                $q->where('start_date', '>=', $today)
                    ->orWhere('end_date', '>=', $today);
            })
            ->with('creator:id,name')
            ->orderBy('start_date')
            ->get()
            ->map(fn (VenueBlockedDate $b) => [
                'id' => $b->id,
                'start_date' => $b->start_date?->toDateString(),
                'end_date' => $b->end_date?->toDateString(),
                'start_time' => $b->start_time ? substr((string) $b->start_time, 0, 5) : null,
                'end_time' => $b->end_time ? substr((string) $b->end_time, 0, 5) : null,
                'reason_type' => $b->reason_type,
                'reason_note' => $b->reason_note,
                'is_recurring' => (bool) $b->is_recurring,
                'created_by' => $b->creator?->name,
            ]);

        $upcomingSpecials = VenueSpecialPricing::query()
            ->where('venue_id', $venue->id)
            ->where(function ($q) use ($today) {
                $q->where('start_date', '>=', $today)
                    ->orWhere('end_date', '>=', $today);
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn (VenueSpecialPricing $sp) => [
                'id' => $sp->id,
                'start_date' => $sp->start_date?->toDateString(),
                'end_date' => $sp->end_date?->toDateString(),
                'price_per_hour' => (int) $sp->price_per_hour,
                'label' => $sp->label,
            ]);

        $locale = app()->getLocale();

        return Inertia::render('Club/Venues/Availability', [
            'venue' => [
                'id' => $venue->id,
                'slug' => $venue->slug,
                'name' => $venue->getTranslation('name', $locale) ?: $venue->getTranslation('name', 'ar'),
            ],
            'calendar' => $calendar,
            'currentMonth' => $anchor->format('Y-m'),
            'monthLabel' => $anchor->isoFormat($locale === 'ar' ? 'MMMM YYYY' : 'MMMM YYYY'),
            'prevMonth' => $anchor->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $anchor->copy()->addMonth()->format('Y-m'),
            'upcomingBlocks' => $upcomingBlocks,
            'upcomingSpecials' => $upcomingSpecials,
        ]);
    }

    public function blockDate(Request $request, Venue $venue): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }
        $this->ensureOwnership($venue, $club);

        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'reason_type' => ['nullable', 'string', 'in:holiday,maintenance,private,other'],
            'reason_note' => ['nullable', 'string', 'max:500'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_pattern' => ['nullable', 'string', 'in:weekly,monthly'],
            'recurrence_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $endDate = $data['end_date'] ?? $data['start_date'];

        $conflicting = Booking::where('venue_id', $venue->id)
            ->whereBetween('booking_date', [$data['start_date'], $endDate])
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Scheduled->value])
            ->count();

        if ($conflicting > 0) {
            return back()
                ->with('flash_key', 'blockHasConflictingBookings')
                ->with('flash_type', 'error');
        }

        VenueBlockedDate::create([
            'venue_id' => $venue->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'reason_type' => $data['reason_type'] ?? null,
            'reason_note' => $data['reason_note'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'created_by' => Auth::id(),
        ]);

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_date_blocked');

        return back()
            ->with('flash_key', 'dateBlocked')
            ->with('flash_type', 'success');
    }

    public function unblockDate(Venue $venue, VenueBlockedDate $block): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }
        $this->ensureOwnership($venue, $club);

        if ((int) $block->venue_id !== (int) $venue->id) {
            abort(404);
        }

        $block->delete();

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_date_unblocked');

        return back()
            ->with('flash_key', 'dateUnblocked')
            ->with('flash_type', 'success');
    }

    public function setSpecialPrice(Request $request, Venue $venue): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }
        $this->ensureOwnership($venue, $club);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'price_per_hour' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:100'],
            'pricing_tier_id' => ['nullable', 'integer', 'exists:venue_pricing_tiers,id'],
        ]);

        VenueSpecialPricing::create([
            'venue_id' => $venue->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'price_per_hour' => $data['price_per_hour'],
            'label' => $data['label'] ?? null,
            'pricing_tier_id' => $data['pricing_tier_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        activity()
            ->performedOn($venue)
            ->causedBy(Auth::user())
            ->log('club_venue_special_price_set');

        return back()
            ->with('flash_key', 'specialPriceSet')
            ->with('flash_type', 'success');
    }

    public function deleteSpecialPrice(Venue $venue, VenueSpecialPricing $special): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }
        $this->ensureOwnership($venue, $club);

        if ((int) $special->venue_id !== (int) $venue->id) {
            abort(404);
        }

        $special->delete();

        return back()
            ->with('flash_key', 'specialPriceRemoved')
            ->with('flash_type', 'success');
    }

    // ---------- helpers ----------

    private function resolveClub(): Club|RedirectResponse
    {
        $user = Auth::user();
        $club = Club::where('owner_id', $user->id)->first() ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.dashboard')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        return $club;
    }

    private function ensureOwnership(Venue $venue, Club $club): void
    {
        if ((int) $venue->club_id !== (int) $club->id) {
            abort(403, 'غير مخوّل للوصول إلى هذا الملعب.');
        }
    }
}
