<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentInitiationService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentInitiationService $paymentService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $query = Payment::query()->with([
            'booking:id,booking_code,user_id,venue_id,status',
            'booking.user:id,name,phone_number',
            'booking.venue:id,club_id,name',
        ]);

        $this->applyFilters($query, $filters);

        $payments = $query
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $p) => $this->indexRow($p));

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'stats' => $this->stats(),
            'options' => $this->options(),
        ]);
    }

    public function show(Payment $payment): Response
    {
        $payment->load([
            'booking:id,booking_code,user_id,venue_id,booking_date,start_time,end_time,status',
            'booking.user:id,name,phone_number,email',
            'booking.venue:id,club_id,slug,name',
            'booking.venue.club:id,slug,name',
            'refundedBy:id,name',
        ]);

        $timeline = Activity::query()
            ->where(function (Builder $q) use ($payment) {
                $q->where('subject_type', Payment::class)->where('subject_id', $payment->id);
            })
            ->with('causer:id,name')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id,
                'description' => $a->description,
                'properties' => $a->properties,
                'causer' => $a->causer?->only(['id', 'name']),
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $this->showPayload($payment),
            'timeline' => $timeline,
            'options' => $this->options(),
        ]);
    }

    public function retry(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Failed) {
            return back()->with('flash_key', 'paymentNotFailed')->with('flash_type', 'error');
        }
        if (! $payment->booking) {
            return back()->with('flash_key', 'paymentRetryFailed')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'provider' => ['nullable', 'in:'.implode(',', array_column(PaymentProvider::cases(), 'value'))],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'send_notification' => ['nullable', 'boolean'],
        ]);

        $provider = $data['provider'] ?? ($payment->provider instanceof PaymentProvider ? $payment->provider->value : (string) $payment->provider);

        // Mark the old payment as cancelled so a new attempt can be created.
        $payment->increment('retry_count');
        $payment->update(['status' => PaymentStatus::Cancelled]);

        try {
            $this->paymentService->initiate(
                bookingId: $payment->booking_id,
                provider: $provider,
                userId: $payment->user_id ?? $request->user()->id,
                phoneNumber: $data['phone_number'] ?? null,
            );
        } catch (\Throwable $e) {
            activity('payment')
                ->performedOn($payment)
                ->causedBy($request->user())
                ->withProperties(['error' => $e->getMessage(), 'provider' => $provider])
                ->log('payment_retry_failed');

            return back()->with('flash_key', 'paymentRetryFailed')->with('flash_type', 'error');
        }

        activity('payment')
            ->performedOn($payment)
            ->causedBy($request->user())
            ->withProperties(['provider' => $provider])
            ->log('payment_retried');

        // TODO: if ($data['send_notification'] ?? false) dispatch(new SendPaymentRetryNotification($payment->fresh()));

        return redirect()->route('admin.payments.show', $payment)
            ->with('flash_key', 'paymentRetried')
            ->with('flash_type', 'success');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Completed) {
            return back()->with('flash_key', 'paymentNotCompleted')->with('flash_type', 'error');
        }
        if ($payment->refunded_at !== null) {
            return back()->with('flash_key', 'paymentAlreadyRefunded')->with('flash_type', 'error');
        }

        $data = $request->validate([
            'refund_amount' => ['required', 'integer', 'min:1', 'max:'.(int) $payment->amount],
            'refund_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'send_notification' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($payment, $data, $request) {
            $payment->update([
                'status' => PaymentStatus::Refunded,
                'refund_amount' => $data['refund_amount'],
                'refund_reason' => $data['refund_reason'],
                'refunded_at' => now(),
                'refunded_by' => $request->user()->id,
            ]);

            activity('payment')
                ->performedOn($payment)
                ->causedBy($request->user())
                ->withProperties([
                    'refund_amount' => $data['refund_amount'],
                    'reason' => $data['refund_reason'],
                ])
                ->log('refund_initiated');
        });

        // NOTE: Gateway-side refund is NOT called here — the payment gateways
        // used by this app (Syriatel Cash, MTN Cash, Fatora, SamaPay) do not
        // expose a refund API in their `Gateway` classes. Refunds on these
        // Syrian mobile-cash rails are executed manually by the finance team;
        // this admin action records the decision and marks the payment as
        // refunded. When gateway refund endpoints are available, wire them
        // here via a match($payment->provider) => $gateway->refund(...) block.

        // TODO: if ($data['send_notification'] ?? false) dispatch(new SendRefundNotification($payment));

        return redirect()->route('admin.payments.show', $payment)
            ->with('flash_key', 'refundInitiated')
            ->with('flash_type', 'success');
    }

    public function failedPayments(Request $request): Response
    {
        $filters = [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'provider' => $request->string('provider')->toString(),
            'retry_bucket' => $request->string('retry_bucket')->toString(), // '', 'never', 'multiple'
        ];

        $query = Payment::query()
            ->where('status', PaymentStatus::Failed)
            ->with([
                'booking:id,booking_code,user_id,venue_id',
                'booking.user:id,name,phone_number',
                'booking.venue:id,name',
            ])
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['provider'], fn ($q, $p) => $q->where('provider', $p))
            ->when($filters['retry_bucket'] === 'never', fn ($q) => $q->where('retry_count', 0))
            ->when($filters['retry_bucket'] === 'multiple', fn ($q) => $q->where('retry_count', '>=', 2))
            ->latest();

        $payments = $query->paginate(20)->withQueryString()->through(fn (Payment $p) => $this->indexRow($p));

        $stats = [
            'total_failed' => (int) Payment::query()->where('status', PaymentStatus::Failed)->count(),
            'never_retried' => (int) Payment::query()->where('status', PaymentStatus::Failed)->where('retry_count', 0)->count(),
            'multiple_retries' => (int) Payment::query()->where('status', PaymentStatus::Failed)->where('retry_count', '>=', 2)->count(),
            'lost_revenue' => (int) Payment::query()->where('status', PaymentStatus::Failed)->sum('amount'),
        ];

        return Inertia::render('Admin/Payments/FailedPayments', [
            'payments' => $payments,
            'filters' => $filters,
            'stats' => $stats,
            'options' => $this->options(),
        ]);
    }

    public function bulkRetry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer'],
        ]);

        $payments = Payment::whereIn('id', $data['payment_ids'])
            ->where('status', PaymentStatus::Failed)
            ->with('booking')
            ->get();

        $success = 0;
        $failed = 0;
        foreach ($payments as $payment) {
            if (! $payment->booking) {
                $failed++;

                continue;
            }

            $payment->increment('retry_count');
            $payment->update(['status' => PaymentStatus::Cancelled]);

            try {
                $this->paymentService->initiate(
                    bookingId: $payment->booking_id,
                    provider: $payment->provider instanceof PaymentProvider ? $payment->provider->value : (string) $payment->provider,
                    userId: $payment->user_id ?? $request->user()->id,
                );
                $success++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return back()
            ->with('flash_key', $failed === 0 ? 'paymentBulkRetried' : 'paymentBulkRetriedPartial')
            ->with('flash_type', $failed === 0 ? 'success' : ($success === 0 ? 'error' : 'success'))
            ->with('bulk_retry_success', $success)
            ->with('bulk_retry_failed', $failed);
    }

    public function export(Request $request): HttpResponse
    {
        $filters = $this->filters($request);
        $query = Payment::query()->with(['booking:id,booking_code,user_id', 'booking.user:id,name']);
        $this->applyFilters($query, $filters);

        $filename = 'payments-'.now()->format('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Payment ID', 'Booking Code', 'Player', 'Provider', 'Amount', 'Currency', 'Status', 'Transaction ID', 'Date']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->booking?->booking_code,
                        $p->booking?->user?->name,
                        $p->provider instanceof PaymentProvider ? $p->provider->value : $p->provider,
                        $p->amount,
                        $p->currency,
                        $p->status instanceof PaymentStatus ? $p->status->value : $p->status,
                        $p->provider_transaction_id,
                        $p->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ---------- helpers ----------

    /** @return array<string, mixed> */
    private function filters(Request $r): array
    {
        return [
            'search' => $r->string('search')->toString(),
            'status' => $r->string('status')->toString(),
            'provider' => $r->string('provider')->toString(),
            'date_from' => $r->string('date_from')->toString(),
            'date_to' => $r->string('date_to')->toString(),
            'amount_min' => $r->input('amount_min'),
            'amount_max' => $r->input('amount_max'),
        ];
    }

    /**
     * @param  Builder<Payment>  $q
     * @param  array<string, mixed>  $f
     */
    private function applyFilters(Builder $q, array $f): void
    {
        $q->when($f['search'], fn ($q, $s) => $q->where(function (Builder $q) use ($s) {
            $q->where('id', 'like', "%{$s}%")
                ->orWhere('provider_transaction_id', 'like', "%{$s}%")
                ->orWhere('provider_reference', 'like', "%{$s}%")
                ->orWhereHas('booking', fn (Builder $q) => $q->where('booking_code', 'like', "%{$s}%"))
                ->orWhereHas('booking.user', fn (Builder $q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone_number', 'like', "%{$s}%"));
        }))
            ->when($f['status'], fn ($q, $s) => $q->where('status', $s))
            ->when($f['provider'], fn ($q, $p) => $q->where('provider', $p))
            ->when($f['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($f['amount_min'] !== null && $f['amount_min'] !== '', fn ($q) => $q->where('amount', '>=', (int) $f['amount_min']))
            ->when($f['amount_max'] !== null && $f['amount_max'] !== '', fn ($q) => $q->where('amount', '<=', (int) $f['amount_max']));
    }

    /** @return array<string, mixed> */
    private function indexRow(Payment $p): array
    {
        return [
            'id' => $p->id,
            'amount' => (int) $p->amount,
            'currency' => $p->currency,
            'provider' => $p->provider instanceof PaymentProvider ? $p->provider->value : $p->provider,
            'status' => $p->status instanceof PaymentStatus ? $p->status->value : $p->status,
            'provider_transaction_id' => $p->provider_transaction_id,
            'provider_reference' => $p->provider_reference,
            'failure_reason' => $p->failure_reason,
            'retry_count' => (int) $p->retry_count,
            'refund_amount' => $p->refund_amount !== null ? (int) $p->refund_amount : null,
            'completed_at' => $p->completed_at?->toIso8601String(),
            'failed_at' => $p->failed_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
            'booking' => $p->booking ? [
                'id' => $p->booking->id,
                'booking_code' => $p->booking->booking_code,
                'user' => $p->booking->user ? ['id' => $p->booking->user->id, 'name' => $p->booking->user->name, 'phone_number' => $p->booking->user->phone_number] : null,
                'venue' => $p->booking->venue ? ['id' => $p->booking->venue->id, 'name' => $p->booking->venue->getTranslations('name')] : null,
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function showPayload(Payment $p): array
    {
        return [
            'id' => $p->id,
            'amount' => (int) $p->amount,
            'currency' => $p->currency,
            'provider' => $p->provider instanceof PaymentProvider ? $p->provider->value : $p->provider,
            'flow_type' => $p->flow_type instanceof PaymentFlowType ? $p->flow_type->value : $p->flow_type,
            'status' => $p->status instanceof PaymentStatus ? $p->status->value : $p->status,
            'provider_transaction_id' => $p->provider_transaction_id,
            'provider_reference' => $p->provider_reference,
            'provider_payload' => $p->provider_payload,
            'failure_reason' => $p->failure_reason,
            'retry_count' => (int) $p->retry_count,
            'refund_amount' => $p->refund_amount !== null ? (int) $p->refund_amount : null,
            'refund_reason' => $p->refund_reason,
            'refunded_at' => $p->refunded_at?->toIso8601String(),
            'refunded_by' => $p->refundedBy ? ['id' => $p->refundedBy->id, 'name' => $p->refundedBy->name] : null,
            'initiated_at' => $p->initiated_at?->toIso8601String(),
            'completed_at' => $p->completed_at?->toIso8601String(),
            'failed_at' => $p->failed_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
            'booking' => $p->booking ? [
                'id' => $p->booking->id,
                'booking_code' => $p->booking->booking_code,
                'booking_date' => $p->booking->booking_date instanceof CarbonInterface ? $p->booking->booking_date->toDateString() : (string) $p->booking->booking_date,
                'start_time' => substr((string) $p->booking->start_time, 0, 5),
                'end_time' => substr((string) $p->booking->end_time, 0, 5),
                'status' => $p->booking->status instanceof BookingStatus ? $p->booking->status->value : $p->booking->status,
                'user' => $p->booking->user ? [
                    'id' => $p->booking->user->id,
                    'name' => $p->booking->user->name,
                    'phone_number' => $p->booking->user->phone_number,
                    'email' => $p->booking->user->email,
                ] : null,
                'venue' => $p->booking->venue ? [
                    'id' => $p->booking->venue->id,
                    'slug' => $p->booking->venue->slug,
                    'name' => $p->booking->venue->getTranslations('name'),
                    'club' => $p->booking->venue->club ? ['id' => $p->booking->venue->club->id, 'slug' => $p->booking->venue->club->slug, 'name' => $p->booking->venue->club->getTranslations('name')] : null,
                ] : null,
            ] : null,
        ];
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $byStatus = Payment::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        return [
            'total' => array_sum($byStatus),
            'completed' => (int) ($byStatus[PaymentStatus::Completed->value] ?? 0),
            'failed' => (int) ($byStatus[PaymentStatus::Failed->value] ?? 0),
            'pending' => (int) ($byStatus[PaymentStatus::Pending->value] ?? 0) + (int) ($byStatus[PaymentStatus::Processing->value] ?? 0),
            'refunded' => (int) ($byStatus[PaymentStatus::Refunded->value] ?? 0),
            'total_revenue' => (int) Payment::query()->where('status', PaymentStatus::Completed)->sum('amount'),
            'today_revenue' => (int) Payment::query()
                ->where('status', PaymentStatus::Completed)
                ->whereDate('completed_at', today())
                ->sum('amount'),
        ];
    }

    /** @return array<string, array<int, string>> */
    private function options(): array
    {
        return [
            'statuses' => array_map(fn ($c) => $c->value, PaymentStatus::cases()),
            'providers' => array_map(fn ($c) => $c->value, PaymentProvider::cases()),
        ];
    }
}
