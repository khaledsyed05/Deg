<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CommissionType;
use App\Enums\DepositStatus;
use App\Enums\ManualType;
use App\Enums\RemainingStatus;
use Carbon\Carbon;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('booking')
            ->logOnly(['venue_id', 'booking_date', 'start_time', 'end_time', 'total_price', 'status'])
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'reminder_2h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
            'remaining_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'manual_type' => ManualType::class,
            'commission_type' => CommissionType::class,
            'deposit_status' => DepositStatus::class,
            'remaining_status' => RemainingStatus::class,
            'recurrence_pattern' => 'array',
            'is_recurring' => 'boolean',
            'is_group_booking' => 'boolean',
            'payment_split_data' => 'array',
            'reschedule_history' => 'array',
        ];
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function remainingConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remaining_confirmed_by');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'recurrence_parent_id');
    }

    public function recurrenceChildren(): HasMany
    {
        return $this->hasMany(Booking::class, 'recurrence_parent_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class);
    }

    public function checkGroupPaymentComplete(): void
    {
        if (! $this->is_group_booking) {
            return;
        }

        $hasUnpaid = $this->participants()->where('payment_status', '!=', 'paid')->exists();

        if (! $hasUnpaid && $this->status === BookingStatus::PendingPayment) {
            $this->update(['status' => BookingStatus::Confirmed]);
        }
    }

    /**
     * @param  array<int, array{user_id:int, amount:int}>|null  $customSplits
     * @return array<string, mixed>
     */
    public function calculatePaymentSplit(string $splitType, ?array $customSplits = null): array
    {
        $total = (int) $this->total_price;
        $groupSize = max(1, (int) $this->group_size);

        return match ($splitType) {
            'full' => [
                'split_type' => 'full',
                'total_amount' => $total,
                'captain_share' => $total,
                'per_player' => 0,
            ],
            'equal' => [
                'split_type' => 'equal',
                'total_amount' => $total,
                'per_player' => intdiv($total, $groupSize),
            ],
            'custom' => [
                'split_type' => 'custom',
                'total_amount' => $total,
                'splits' => $customSplits ?? [],
            ],
            default => [
                'split_type' => $splitType,
                'total_amount' => $total,
            ],
        };
    }

    protected static function booted(): void
    {
        static::creating(function (self $booking): void {
            if (empty($booking->qr_code)) {
                $booking->qr_code = QrCode::format('svg')
                    ->size(300)
                    ->margin(1)
                    ->generate($booking->booking_code ?: uniqid('BK-', true));
            }
        });
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Scheduled, BookingStatus::CheckedIn])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereIn('status', [BookingStatus::Completed, BookingStatus::NoShow, BookingStatus::Cancelled])
                    ->orWhere('ends_at', '<', now());
            })
            ->orderByDesc('starts_at');
    }

    public function bookingStart(): Carbon
    {
        return $this->starts_at instanceof Carbon
            ? $this->starts_at
            : Carbon::parse($this->booking_date->format('Y-m-d').' '.$this->start_time);
    }

    public function bookingEnd(): Carbon
    {
        return $this->ends_at instanceof Carbon
            ? $this->ends_at
            : Carbon::parse($this->booking_date->format('Y-m-d').' '.$this->end_time);
    }

    public function isPast(): bool
    {
        return $this->bookingEnd()->isPast();
    }

    public function isToday(): bool
    {
        return $this->booking_date?->isToday() ?? false;
    }

    public function canBeCancelled(): bool
    {
        if (! in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Scheduled], true)) {
            return false;
        }

        return $this->bookingStart()->isFuture();
    }

    public function calculateCancellationFee(): int
    {
        if (! $this->canBeCancelled()) {
            return 0;
        }

        $freeHours = data_get($this->venue?->club?->settings, 'booking_rules.cancellation_hours', 24);
        $hoursUntil = now()->diffInHours($this->bookingStart(), false);

        if ($hoursUntil >= $freeHours) {
            return 0;
        }

        return (int) round((int) $this->deposit_amount * 0.20);
    }

    public function refundAmount(): int
    {
        return max(0, (int) $this->deposit_amount - $this->calculateCancellationFee());
    }

    /**
     * Generate a human-readable booking code in the format BK-YYYY-MM-NNNNN.
     * The sequence resets monthly and retries on collision.
     */
    public static function generateBookingCode(?\DateTimeInterface $at = null): string
    {
        $now = $at ? Carbon::instance($at) : now();
        $prefix = sprintf('BK-%s', $now->format('Y-m'));

        $lastSequence = (int) self::query()
            ->where('booking_code', 'like', $prefix.'-%')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(booking_code, '-', -1) AS UNSIGNED)) AS seq")
            ->value('seq');

        $sequence = $lastSequence + 1;

        do {
            $candidate = sprintf('%s-%05d', $prefix, $sequence);
            $exists = self::where('booking_code', $candidate)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $candidate;
    }

    public function checkIn(): void
    {
        $this->update([
            'status' => BookingStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);

        activity('booking')
            ->causedBy($this->user)
            ->performedOn($this)
            ->event('checked_in')
            ->log('User checked in to booking');
    }
}
