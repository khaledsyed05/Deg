<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\DepositStatus;
use App\Enums\RemainingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Services\Booking\BookingService;
use App\Services\Notification\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class BookingController extends Controller
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private BookingService $service,
        private ClubRepositoryInterface $clubs,
        private CityRepositoryInterface $cities,
        private WhatsAppService $whatsapp,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $query = $this->bookings->query()
            ->with([
                'user:id,name,phone_number',
                'venue:id,club_id,slug,name',
                'venue.club:id,slug,name,city_id',
                'venue.club.city:id,name,name_ar',
            ]);

        $this->applyFilters($query, $filters);

        $bookings = $query
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $b) => $this->indexRow($b));

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => $this->options(),
        ]);
    }

    public function show(Booking $booking): Response
    {
        $booking->load([
            'user:id,name,phone_number,email',
            'venue:id,club_id,slug,category_id,name,latitude,longitude',
            'venue.club:id,slug,name,city_id,phone_number,address',
            'venue.club.city:id,name,name_ar',
            'venue.category:id,name',
            'payments' => fn ($q) => $q->latest(),
            'cancelledBy:id,name',
        ]);

        $coverPhoto = optional($booking->venue?->getMedia('images')->sortBy('order_column')->first())->getUrl();
        $activity = Activity::where('log_name', 'booking')
            ->where('subject_type', Booking::class)
            ->where('subject_id', $booking->id)
            ->with('causer:id,name')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'properties' => $a->properties,
                'causer' => $a->causer?->only(['id', 'name']),
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        $playerBookingsCount = $booking->user
            ? $booking->user->bookings()->where('id', '!=', $booking->id)->count()
            : 0;

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $this->showPayload($booking, $coverPhoto),
            'activity' => $activity,
            'player_bookings_count' => $playerBookingsCount,
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'reason_type' => ['required', 'in:player_request,technical_issue,weather,other'],
            'custom_reason' => ['nullable', 'string', 'max:1000', 'required_if:reason_type,other', 'min:5'],
            'notify_whatsapp' => ['nullable', 'boolean'],
        ]);

        $reason = match ($data['reason_type']) {
            'player_request' => 'طلب اللاعب',
            'technical_issue' => 'عطل فني في الملعب',
            'weather' => 'ظروف جوية',
            'other' => $data['custom_reason'],
        };

        try {
            $this->service->cancel($booking, $request->user()->id, $reason);
        } catch (RuntimeException $e) {
            return back()->with('flash_key', 'bookingCancelBlocked')->with('flash_type', 'error');
        }

        if ($data['notify_whatsapp'] ?? false) {
            $this->notifyCancellation($booking->fresh(), $reason);
        }

        return back()->with('flash_key', 'bookingCancelled')->with('flash_type', 'success');
    }

    public function modify(Request $request, Booking $booking): RedirectResponse
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Scheduled], true)) {
            return back()->with('flash_key', 'bookingModifyBlocked')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notify_whatsapp' => ['nullable', 'boolean'],
        ]);

        $conflict = Booking::where('venue_id', $booking->venue_id)
            ->where('id', '!=', $booking->id)
            ->where('booking_date', $data['booking_date'])
            ->whereIn('status', ['confirmed', 'scheduled'])
            ->where(function (Builder $q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    ->orWhere(function (Builder $q) use ($data) {
                        $q->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                    });
            })
            ->exists();

        if ($conflict) {
            return back()->with('flash_key', 'bookingSlotTaken')->with('flash_type', 'error');
        }

        $start = Carbon::parse($data['booking_date'].' '.$data['start_time']);
        $end = Carbon::parse($data['booking_date'].' '.$data['end_time']);

        $booking->update([
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'starts_at' => $start,
            'ends_at' => $end,
            'duration_minutes' => $start->diffInMinutes($end),
        ]);

        if ($data['notify_whatsapp'] ?? false) {
            $this->notifyModification($booking->fresh());
        }

        return back()->with('flash_key', 'bookingModified')->with('flash_type', 'success');
    }

    public function calendar(Request $request): Response
    {
        $monthParam = $request->string('month')->toString();
        try {
            $anchor = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            $anchor = Carbon::now()->startOfMonth();
        }

        $rangeStart = $anchor->copy();
        $rangeEnd = $anchor->copy()->endOfMonth();

        $venueId = $request->integer('venue_id') ?: null;
        $clubId = $request->integer('club_id') ?: null;

        $bookings = $this->bookings->query()
            ->with(['user:id,name', 'venue:id,club_id,name', 'venue.club:id,name'])
            ->whereBetween('booking_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->when($venueId, fn ($q) => $q->where('venue_id', $venueId))
            ->when($clubId, fn ($q) => $q->whereHas('venue', fn (Builder $q) => $q->where('club_id', $clubId)))
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'date' => $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : (string) $b->booking_date,
                'start_time' => substr((string) $b->start_time, 0, 5),
                'end_time' => substr((string) $b->end_time, 0, 5),
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'player' => $b->user?->name,
                'venue' => $b->venue?->getTranslations('name'),
                'club' => $b->venue?->club?->getTranslations('name'),
                'total_price' => (int) $b->total_price,
            ]);

        return Inertia::render('Admin/Bookings/Calendar', [
            'bookings' => $bookings,
            'month' => $anchor->format('Y-m'),
            'filters' => ['venue_id' => $venueId, 'club_id' => $clubId],
            'options' => [
                'clubs' => $this->clubOptions(),
                'venues' => $this->venueOptions($clubId),
            ],
        ]);
    }

    public function export(Request $request): HttpResponse
    {
        // Lightweight CSV export (no external dependency). For a full Excel export,
        // install maatwebsite/excel and swap this endpoint to return an .xlsx.
        $filters = $this->filters($request);
        $query = $this->bookings->query()
            ->with(['user:id,name,phone_number', 'venue:id,club_id,name', 'venue.club:id,name']);
        $this->applyFilters($query, $filters);

        $filename = 'bookings-'.now()->format('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, ['Code', 'Player', 'Phone', 'Venue', 'Club', 'Date', 'Start', 'End', 'Total', 'Status', 'Deposit']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $b) {
                    fputcsv($out, [
                        $b->booking_code,
                        $b->user?->name,
                        $b->user?->phone_number,
                        $b->venue?->getTranslation('name', 'ar'),
                        $b->venue?->club?->getTranslation('name', 'ar'),
                        $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : $b->booking_date,
                        substr((string) $b->start_time, 0, 5),
                        substr((string) $b->end_time, 0, 5),
                        $b->total_price,
                        $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                        $b->deposit_status instanceof DepositStatus ? $b->deposit_status->value : $b->deposit_status,
                    ]);
                }
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function resendConfirmation(Booking $booking): RedirectResponse
    {
        $booking->load('user:id,name,phone_number', 'venue:id,club_id,name', 'venue.club:id');

        $phone = $booking->user?->phone_number;
        $clubId = $booking->venue?->club_id;
        if (! $phone || ! $clubId) {
            return back()->with('flash_key', 'bookingNotifyFailed')->with('flash_type', 'error');
        }

        $message = $this->buildConfirmationMessage($booking);
        $ok = $this->whatsapp->sendMessage($clubId, $phone, $message);

        return back()
            ->with('flash_key', $ok ? 'bookingNotifySent' : 'bookingNotifyFailed')
            ->with('flash_type', $ok ? 'success' : 'error');
    }

    public function sendReminder(Booking $booking): RedirectResponse
    {
        $booking->load('user:id,name,phone_number', 'venue:id,club_id,name', 'venue.club:id');

        $phone = $booking->user?->phone_number;
        $clubId = $booking->venue?->club_id;
        if (! $phone || ! $clubId) {
            return back()->with('flash_key', 'bookingNotifyFailed')->with('flash_type', 'error');
        }

        $message = $this->buildReminderMessage($booking);
        $ok = $this->whatsapp->sendMessage($clubId, $phone, $message);

        return back()
            ->with('flash_key', $ok ? 'bookingNotifySent' : 'bookingNotifyFailed')
            ->with('flash_type', $ok ? 'success' : 'error');
    }

    // ---------- helpers ----------

    /** @return array<string, mixed> */
    private function filters(Request $r): array
    {
        return [
            'search' => $r->string('search')->toString(),
            'status' => $r->string('status')->toString(),
            'deposit_status' => $r->string('deposit_status')->toString(),
            'date_from' => $r->string('date_from')->toString(),
            'date_to' => $r->string('date_to')->toString(),
            'venue_id' => $r->integer('venue_id') ?: null,
            'club_id' => $r->integer('club_id') ?: null,
            'city_id' => $r->integer('city_id') ?: null,
            'category_id' => $r->integer('category_id') ?: null,
            'amount_min' => $r->input('amount_min'),
            'amount_max' => $r->input('amount_max'),
            'created_from' => $r->string('created_from')->toString(),
            'created_to' => $r->string('created_to')->toString(),
        ];
    }

    /**
     * @param  Builder<Booking>  $q
     * @param  array<string, mixed>  $f
     */
    private function applyFilters(Builder $q, array $f): void
    {
        $q->when($f['search'], fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
            $q->where('booking_code', 'like', "%{$s}%")
                ->orWhereHas('user', fn (Builder $q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone_number', 'like', "%{$s}%"))
                ->orWhereHas('venue', fn (Builder $q) => $q->where('name->ar', 'like', "%{$s}%")->orWhere('name->en', 'like', "%{$s}%"));
        }))
            ->when($f['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($f['deposit_status'], fn ($q, $s) => $q->where('deposit_status', $s))
            ->when($f['date_from'], fn ($q, $d) => $q->where('booking_date', '>=', $d))
            ->when($f['date_to'], fn ($q, $d) => $q->where('booking_date', '<=', $d))
            ->when($f['venue_id'], fn ($q, $id) => $q->where('venue_id', $id))
            ->when($f['club_id'], fn ($q, $id) => $q->whereHas('venue', fn (Builder $q) => $q->where('club_id', $id)))
            ->when($f['city_id'], fn ($q, $id) => $q->whereHas('venue.club', fn (Builder $q) => $q->where('city_id', $id)))
            ->when($f['category_id'], fn ($q, $id) => $q->whereHas('venue', fn (Builder $q) => $q->where('category_id', $id)))
            ->when($f['amount_min'] !== null && $f['amount_min'] !== '', fn ($q) => $q->where('total_price', '>=', (int) $f['amount_min']))
            ->when($f['amount_max'] !== null && $f['amount_max'] !== '', fn ($q) => $q->where('total_price', '<=', (int) $f['amount_max']))
            ->when($f['created_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['created_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    /** @return array<string, mixed> */
    private function indexRow(Booking $b): array
    {
        return [
            'id' => $b->id,
            'booking_code' => $b->booking_code,
            'booking_date' => $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : (string) $b->booking_date,
            'start_time' => substr((string) $b->start_time, 0, 5),
            'end_time' => substr((string) $b->end_time, 0, 5),
            'duration_minutes' => (int) $b->duration_minutes,
            'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
            'deposit_status' => $b->deposit_status instanceof DepositStatus ? $b->deposit_status->value : $b->deposit_status,
            'total_price' => (int) $b->total_price,
            'deposit_amount' => (int) $b->deposit_amount,
            'remaining_amount' => (int) $b->remaining_amount,
            'user' => $b->user ? ['id' => $b->user->id, 'name' => $b->user->name, 'phone_number' => $b->user->phone_number] : null,
            'venue' => $b->venue ? [
                'id' => $b->venue->id,
                'slug' => $b->venue->slug,
                'name' => $b->venue->getTranslations('name'),
                'club' => $b->venue->club ? [
                    'id' => $b->venue->club->id,
                    'slug' => $b->venue->club->slug,
                    'name' => $b->venue->club->getTranslations('name'),
                    'city' => $b->venue->club->city ? ['id' => $b->venue->club->city->id, 'name' => $b->venue->club->city->name, 'name_ar' => $b->venue->club->city->name_ar] : null,
                ] : null,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function showPayload(Booking $b, ?string $coverUrl): array
    {
        return [
            'id' => $b->id,
            'booking_code' => $b->booking_code,
            'source' => $b->source instanceof BookingSource ? $b->source->value : $b->source,
            'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
            'deposit_status' => $b->deposit_status instanceof DepositStatus ? $b->deposit_status->value : $b->deposit_status,
            'remaining_status' => $b->remaining_status instanceof RemainingStatus ? $b->remaining_status->value : $b->remaining_status,
            'booking_date' => $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : (string) $b->booking_date,
            'start_time' => substr((string) $b->start_time, 0, 5),
            'end_time' => substr((string) $b->end_time, 0, 5),
            'duration_minutes' => (int) $b->duration_minutes,
            'venue_price' => (int) $b->venue_price,
            'total_price' => (int) $b->total_price,
            'deposit_amount' => (int) $b->deposit_amount,
            'remaining_amount' => (int) $b->remaining_amount,
            'commission_amount' => (int) $b->commission_amount,
            'club_payout_amount' => (int) $b->club_payout_amount,
            'currency' => $b->currency,
            'notes' => $b->notes,
            'cancellation_reason' => $b->cancellation_reason,
            'cancelled_at' => $b->cancelled_at?->toIso8601String(),
            'created_at' => $b->created_at?->toIso8601String(),
            'cancelled_by' => $b->cancelledBy ? ['id' => $b->cancelledBy->id, 'name' => $b->cancelledBy->name] : null,
            'user' => $b->user ? ['id' => $b->user->id, 'name' => $b->user->name, 'phone_number' => $b->user->phone_number, 'email' => $b->user->email] : null,
            'venue' => $b->venue ? [
                'id' => $b->venue->id,
                'slug' => $b->venue->slug,
                'name' => $b->venue->getTranslations('name'),
                'latitude' => $b->venue->latitude,
                'longitude' => $b->venue->longitude,
                'cover_url' => $coverUrl,
                'category' => $b->venue->category ? ['id' => $b->venue->category->id, 'name' => $b->venue->category->getTranslations('name')] : null,
                'club' => $b->venue->club ? [
                    'id' => $b->venue->club->id,
                    'slug' => $b->venue->club->slug,
                    'name' => $b->venue->club->getTranslations('name'),
                    'phone_number' => $b->venue->club->phone_number,
                    'address' => $b->venue->club->address,
                    'city' => $b->venue->club->city ? ['id' => $b->venue->club->city->id, 'name' => $b->venue->club->city->name, 'name_ar' => $b->venue->club->city->name_ar] : null,
                ] : null,
            ] : null,
            'payments' => $b->payments->map(fn ($p) => [
                'id' => $p->id,
                'provider' => $p->provider,
                'amount' => (int) $p->amount,
                'status' => $p->status,
                'provider_transaction_id' => $p->provider_transaction_id,
                'completed_at' => $p->completed_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ]),
        ];
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $byStatus = $this->bookings->query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $today = Carbon::today()->toDateString();
        $todayCount = $this->bookings->query()->where('booking_date', $today)->count();

        $revenue = (int) $this->bookings->query()
            ->whereIn('status', ['confirmed', 'completed', 'scheduled'])
            ->sum('total_price');

        $pendingPayments = $this->bookings->query()
            ->where('deposit_status', DepositStatus::None->value)
            ->whereIn('status', ['confirmed', 'scheduled'])
            ->count();

        return [
            'total' => array_sum($byStatus),
            'confirmed' => (int) ($byStatus[BookingStatus::Confirmed->value] ?? 0),
            'scheduled' => (int) ($byStatus[BookingStatus::Scheduled->value] ?? 0),
            'completed' => (int) ($byStatus[BookingStatus::Completed->value] ?? 0),
            'cancelled' => (int) ($byStatus[BookingStatus::Cancelled->value] ?? 0),
            'revenue' => $revenue,
            'pending_payments' => $pendingPayments,
            'today' => $todayCount,
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function options(): array
    {
        return [
            'clubs' => $this->clubOptions(),
            'cities' => $this->cities->query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'name_ar'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'name_ar' => $c->name_ar])
                ->all(),
            'statuses' => array_map(fn ($c) => $c->value, BookingStatus::cases()),
            'deposit_statuses' => array_map(fn ($c) => $c->value, DepositStatus::cases()),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function clubOptions(): array
    {
        return $this->clubs->query()
            ->where('status', 'active')
            ->orderBy('name->ar')
            ->get(['id', 'name', 'city_id'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->getTranslations('name'), 'city_id' => $c->city_id])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function venueOptions(?int $clubId = null): array
    {
        return Venue::query()
            ->when($clubId, fn ($q) => $q->where('club_id', $clubId))
            ->orderBy('name->ar')
            ->get(['id', 'club_id', 'name'])
            ->map(fn ($v) => ['id' => $v->id, 'club_id' => $v->club_id, 'name' => $v->getTranslations('name')])
            ->all();
    }

    private function notifyCancellation(Booking $b, string $reason): void
    {
        $phone = $b->user?->phone_number;
        $clubId = $b->venue?->club_id;
        if (! $phone || ! $clubId) {
            return;
        }
        $message = sprintf(
            "❌ تم إلغاء حجزك\n\nكود: %s\nالتاريخ: %s\nالوقت: %s - %s\n\nالسبب: %s",
            $b->booking_code,
            $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : $b->booking_date,
            substr((string) $b->start_time, 0, 5),
            substr((string) $b->end_time, 0, 5),
            $reason,
        );
        $this->whatsapp->sendMessage($clubId, $phone, $message);
    }

    private function notifyModification(Booking $b): void
    {
        $phone = $b->user?->phone_number;
        $clubId = $b->venue?->club_id;
        if (! $phone || ! $clubId) {
            return;
        }
        $message = sprintf(
            "🔄 تم تعديل موعد حجزك\n\nكود: %s\nالتاريخ الجديد: %s\nالوقت الجديد: %s - %s",
            $b->booking_code,
            $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : $b->booking_date,
            substr((string) $b->start_time, 0, 5),
            substr((string) $b->end_time, 0, 5),
        );
        $this->whatsapp->sendMessage($clubId, $phone, $message);
    }

    private function buildConfirmationMessage(Booking $b): string
    {
        $venueName = $b->venue?->getTranslation('name', 'ar') ?? '';
        $date = $b->booking_date instanceof Carbon ? $b->booking_date->toDateString() : $b->booking_date;

        return sprintf(
            "✅ تأكيد حجزك\n\nكود: %s\nالملعب: %s\nالتاريخ: %s\nالوقت: %s - %s\nالإجمالي: %s ل.س",
            $b->booking_code,
            $venueName,
            $date,
            substr((string) $b->start_time, 0, 5),
            substr((string) $b->end_time, 0, 5),
            number_format((int) $b->total_price),
        );
    }

    private function buildReminderMessage(Booking $b): string
    {
        $remaining = number_format((int) $b->remaining_amount);

        return sprintf(
            "⏰ تذكير بالدفع\n\nكود: %s\nالمبلغ المتبقي: %s ل.س\nيُرجى إتمام الدفع في أقرب وقت.",
            $b->booking_code,
            $remaining,
        );
    }
}
