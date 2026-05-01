<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Notification\SettlementPaidNotificationJob;
use App\Models\Settlement;
use App\Repositories\Contracts\ClubRepositoryInterface;
use App\Repositories\Contracts\SettlementRepositoryInterface;
use App\Services\Payment\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Yajra\DataTables\Facades\DataTables;

class SettlementController extends Controller
{
    public function __construct(
        private SettlementRepositoryInterface $settlementRepo,
        private SettlementService $settlementService,
        private ClubRepositoryInterface $clubRepo,
    ) {}

    public function index(): Response
    {
        $clubs = $this->clubRepo->query()
            ->select('id', 'name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->getTranslation('name', app()->getLocale())]);

        return Inertia::render('Admin/Settlements/Index', [
            'clubs' => $clubs,
        ]);
    }

    public function datatables(): JsonResponse
    {
        $query = Settlement::query()
            ->with('club:id,name')
            ->select(['settlements.*']);

        return DataTables::of($query)
            ->addColumn('club_name', fn (Settlement $s) => $s->club?->getTranslation('name', app()->getLocale()) ?? '')
            ->addColumn('period', fn (Settlement $s) => ($s->period_from?->toDateString() ?? '').' — '.($s->period_to?->toDateString() ?? ''))
            ->filterColumn('club_name', function ($query, $keyword) {
                $locale = app()->getLocale();
                $query->whereHas('club', fn ($q) => $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.".$locale."')) LIKE ?", ["%{$keyword}%"]));
            })
            ->only(['id', 'club_name', 'period', 'net_payable', 'status'])
            ->make(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $this->settlementService->draft(
            clubId: $request->club_id,
            periodFrom: $request->from_date,
            periodTo: $request->to_date,
            createdBy: $request->user()->id,
        );

        return redirect()->route('admin.settlements.index')
            ->with('success', 'تم إنشاء التسوية بنجاح');
    }

    public function show(Settlement $settlement): Response
    {
        $settlement->load([
            'club:id,slug,name,phone_number,address',
            'club.owner:id,name,phone_number,email',
            'settledBy:id,name',
            'notesUpdatedBy:id,name',
            'items.booking:id,booking_code,user_id,venue_id,booking_date,start_time,end_time,total_price,deposit_amount,remaining_amount,status',
            'items.booking.user:id,name',
            'items.booking.venue:id,club_id,name',
        ]);

        return Inertia::render('Admin/Settlements/Show', [
            'settlement' => $this->showPayload($settlement),
            'items' => $settlement->items->map(fn ($it) => $this->itemRow($it))->values(),
        ]);
    }

    public function markAsPaid(Request $request, Settlement $settlement): RedirectResponse
    {
        if ($settlement->status === 'paid') {
            return back()->with('flash_key', 'settlementAlreadyPaid')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,cash,cheque,other'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
            'send_notification' => ['nullable', 'boolean'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('settlements/receipts', 'public');
        }

        $settlement->update([
            'status' => 'paid',
            'payment_method' => $data['payment_method'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_notes' => $data['payment_notes'] ?? null,
            'receipt_path' => $receiptPath,
            'settled_at' => $data['payment_date'],
            'settled_by' => $request->user()->id,
            'paid_amount' => $settlement->net_payable,
        ]);

        activity('settlement')
            ->performedOn($settlement)
            ->causedBy($request->user())
            ->withProperties([
                'payment_method' => $data['payment_method'],
                'amount' => (int) $settlement->net_payable,
            ])
            ->log('settlement_marked_as_paid');

        if ($data['send_notification'] ?? false) {
            SettlementPaidNotificationJob::dispatch($settlement->fresh());
        }

        return redirect()->route('admin.settlements.show', $settlement)
            ->with('flash_key', 'settlementPaid')
            ->with('flash_type', 'success');
    }

    public function updateNotes(Request $request, Settlement $settlement): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement->update([
            'admin_notes' => $data['admin_notes'],
            'notes_updated_at' => now(),
            'notes_updated_by' => $request->user()->id,
        ]);

        return back()
            ->with('flash_key', 'settlementNotesUpdated')
            ->with('flash_type', 'success');
    }

    public function exportExcel(Settlement $settlement): HttpResponse
    {
        $settlement->load([
            'club:id,name',
            'items.booking:id,booking_code,user_id,venue_id,booking_date,start_time,end_time,total_price,deposit_amount,remaining_amount',
            'items.booking.user:id,name',
            'items.booking.venue:id,name',
        ]);

        $filename = "settlement_{$settlement->id}_".now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($settlement) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, ['Settlement Summary']);
            fputcsv($out, ['Settlement ID', $settlement->id]);
            fputcsv($out, ['Club', $settlement->club?->getTranslation('name', 'ar') ?? '']);
            fputcsv($out, ['Period From', $settlement->period_from?->toDateString()]);
            fputcsv($out, ['Period To', $settlement->period_to?->toDateString()]);
            fputcsv($out, ['Total Bookings', $settlement->total_bookings]);
            fputcsv($out, ['Total Revenue', $settlement->total_venue_price]);
            fputcsv($out, ['Commission', $settlement->total_commission]);
            fputcsv($out, ['Cancellation Fees', $settlement->total_cancellation_fees]);
            fputcsv($out, ['Net Payable', $settlement->net_payable]);
            fputcsv($out, ['Paid Amount', $settlement->paid_amount]);
            fputcsv($out, ['Status', $settlement->status]);
            fputcsv($out, ['Settled At', $settlement->settled_at?->toIso8601String()]);
            fputcsv($out, []);

            fputcsv($out, ['Bookings Breakdown']);
            fputcsv($out, [
                'Booking Code', 'Date', 'Time', 'Player', 'Venue',
                'Total', 'Deposit', 'Remaining',
                'Venue Price', 'Commission', 'Cancellation', 'Club Payout',
            ]);
            foreach ($settlement->items as $item) {
                $b = $item->booking;
                fputcsv($out, [
                    $b?->booking_code,
                    $b?->booking_date?->toDateString(),
                    $b ? substr((string) $b->start_time, 0, 5).' - '.substr((string) $b->end_time, 0, 5) : '',
                    $b?->user?->name,
                    $b?->venue?->getTranslation('name', 'ar'),
                    $b?->total_price,
                    $b?->deposit_amount,
                    $b?->remaining_amount,
                    $item->venue_price,
                    $item->commission_amount,
                    $item->cancellation_comm,
                    $item->club_payout_amount,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Settlement $settlement): RedirectResponse
    {
        // DomPDF is not installed in this codebase. Falling back to CSV and
        // flashing a note. To enable PDF: `composer require barryvdh/laravel-dompdf`
        // then replace this body with Pdf::loadView('admin.settlements.pdf', ...)->download(...).
        return redirect()->route('admin.settlements.export-excel', $settlement)
            ->with('flash_key', 'settlementPdfUnavailable')
            ->with('flash_type', 'info');
    }

    // ---------- helpers ----------

    /** @return array<string, mixed> */
    private function showPayload(Settlement $s): array
    {
        return [
            'id' => $s->id,
            'status' => $s->status,
            'period_from' => $s->period_from?->toDateString(),
            'period_to' => $s->period_to?->toDateString(),
            'total_bookings' => (int) $s->total_bookings,
            'total_venue_price' => (int) $s->total_venue_price,
            'total_commission' => (int) $s->total_commission,
            'total_cancellation_fees' => (int) $s->total_cancellation_fees,
            'net_payable' => (int) $s->net_payable,
            'paid_amount' => (int) $s->paid_amount,
            'payment_method' => $s->payment_method,
            'payment_reference' => $s->payment_reference,
            'payment_notes' => $s->payment_notes,
            'admin_notes' => $s->admin_notes,
            'receipt_url' => $s->receipt_path ? Storage::url($s->receipt_path) : null,
            'receipt_filename' => $s->receipt_path ? basename($s->receipt_path) : null,
            'settled_at' => $s->settled_at?->toIso8601String(),
            'settled_by' => $s->settledBy ? ['id' => $s->settledBy->id, 'name' => $s->settledBy->name] : null,
            'notes_updated_at' => $s->notes_updated_at?->toIso8601String(),
            'notes_updated_by' => $s->notesUpdatedBy ? ['id' => $s->notesUpdatedBy->id, 'name' => $s->notesUpdatedBy->name] : null,
            'created_at' => $s->created_at?->toIso8601String(),
            'club' => $s->club ? [
                'id' => $s->club->id,
                'slug' => $s->club->slug,
                'name' => $s->club->getTranslations('name'),
                'phone_number' => $s->club->phone_number,
                'address' => $s->club->address,
                'owner' => $s->club->owner ? [
                    'id' => $s->club->owner->id,
                    'name' => $s->club->owner->name,
                    'phone_number' => $s->club->owner->phone_number,
                    'email' => $s->club->owner->email,
                ] : null,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function itemRow($item): array
    {
        $b = $item->booking;

        return [
            'id' => $item->id,
            'venue_price' => (int) $item->venue_price,
            'commission_amount' => (int) $item->commission_amount,
            'club_payout_amount' => (int) $item->club_payout_amount,
            'cancellation_comm' => (int) $item->cancellation_comm,
            'booking' => $b ? [
                'id' => $b->id,
                'booking_code' => $b->booking_code,
                'booking_date' => $b->booking_date?->toDateString(),
                'start_time' => substr((string) $b->start_time, 0, 5),
                'end_time' => substr((string) $b->end_time, 0, 5),
                'total_price' => (int) $b->total_price,
                'deposit_amount' => (int) $b->deposit_amount,
                'remaining_amount' => (int) $b->remaining_amount,
                'status' => $b->status instanceof BookingStatus ? $b->status->value : $b->status,
                'user' => $b->user ? ['id' => $b->user->id, 'name' => $b->user->name] : null,
                'venue' => $b->venue ? ['id' => $b->venue->id, 'name' => $b->venue->getTranslations('name')] : null,
            ] : null,
        ];
    }
}
