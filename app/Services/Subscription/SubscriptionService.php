<?php

namespace App\Services\Subscription;

use App\Enums\RecurrenceFrequency;
use App\Models\BookingInstance;
use App\Models\Subscription;
use App\Models\Venue;
use App\Services\Booking\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subscription
    {
        $venue = Venue::findOrFail($data['venue_id']);
        $durationHours = (int) $data['duration_hours'];
        $frequency = RecurrenceFrequency::from($data['frequency']);

        $regularHourly = (int) ($venue->price_from ?? 0);
        $regularPrice = $regularHourly * $durationHours;
        $discountPercentage = $frequency->discount();
        $pricePerBooking = (int) round($regularPrice - ($regularPrice * $discountPercentage / 100));

        $startDate = Carbon::parse($data['start_date']);
        $firstOccurrence = $this->calculateFirstOccurrence($startDate, $data);

        $subscription = Subscription::create([
            'user_id' => $data['user_id'],
            'venue_id' => $data['venue_id'],
            'frequency' => $frequency->value,
            'interval' => $data['interval'] ?? null,
            'day_of_week' => $data['day_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'start_time' => $data['start_time'],
            'duration_hours' => $durationHours,
            'start_date' => $startDate->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'status' => 'active',
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'auto_pay' => $data['auto_pay'] ?? true,
            'price_per_booking' => $pricePerBooking,
            'discount_percentage' => $discountPercentage,
            'next_booking_date' => $firstOccurrence->toDateString(),
            'next_charge_date' => $firstOccurrence->copy()->subDays(3)->toDateString(),
        ]);

        $this->createInstancesForNext7Days($subscription->fresh());

        return $subscription->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function calculateFirstOccurrence(Carbon $startDate, array $data): Carbon
    {
        $frequency = RecurrenceFrequency::from($data['frequency']);

        if ($frequency === RecurrenceFrequency::Weekly || $frequency === RecurrenceFrequency::Biweekly) {
            $daysMap = [
                'monday' => Carbon::MONDAY,
                'tuesday' => Carbon::TUESDAY,
                'wednesday' => Carbon::WEDNESDAY,
                'thursday' => Carbon::THURSDAY,
                'friday' => Carbon::FRIDAY,
                'saturday' => Carbon::SATURDAY,
                'sunday' => Carbon::SUNDAY,
            ];

            $target = $daysMap[$data['day_of_week'] ?? ''] ?? null;

            if ($target === null) {
                return $startDate->copy();
            }

            return $startDate->dayOfWeek === $target
                ? $startDate->copy()
                : $startDate->copy()->next($target);
        }

        if ($frequency === RecurrenceFrequency::Monthly) {
            $dayOfMonth = (int) ($data['day_of_month'] ?? $startDate->day);
            $next = $startDate->copy()->day(min($dayOfMonth, $startDate->daysInMonth));

            if ($next->lt($startDate)) {
                $next->addMonth()->day(min($dayOfMonth, $next->daysInMonth));
            }

            return $next;
        }

        return $startDate->copy();
    }

    public function createInstancesForNext7Days(Subscription $subscription): void
    {
        if (! $subscription->isActive() || ! $subscription->next_booking_date) {
            return;
        }

        $current = $subscription->next_booking_date->copy();
        $endWindow = now()->addDays(7)->startOfDay();

        while ($current->lte($endWindow)) {
            $exists = BookingInstance::where('subscription_id', $subscription->id)
                ->whereDate('scheduled_date', $current->toDateString())
                ->exists();

            if (! $exists) {
                $start = Carbon::parse($subscription->start_time);
                $end = $start->copy()->addHours((int) $subscription->duration_hours);

                BookingInstance::create([
                    'subscription_id' => $subscription->id,
                    'scheduled_date' => $current->toDateString(),
                    'scheduled_start_time' => $start->format('H:i:s'),
                    'scheduled_end_time' => $end->format('H:i:s'),
                    'status' => 'scheduled',
                    'payment_status' => 'pending',
                ]);
            }

            $current = match ($subscription->frequency) {
                RecurrenceFrequency::Weekly => $current->addWeek(),
                RecurrenceFrequency::Biweekly => $current->addWeeks(2),
                RecurrenceFrequency::Monthly => $current->addMonth(),
                RecurrenceFrequency::Custom => $current->addDays((int) ($subscription->interval ?? 1)),
            };

            if ($subscription->end_date && $current->gt($subscription->end_date)) {
                break;
            }
        }
    }

    public function processDueSubscriptions(): int
    {
        $count = 0;

        Subscription::dueForProcessing()->with('user', 'venue')->chunk(50, function ($subs) use (&$count) {
            foreach ($subs as $subscription) {
                $this->processSubscription($subscription);
                $count++;
            }
        });

        return $count;
    }

    protected function processSubscription(Subscription $subscription): void
    {
        $instances = $subscription->instances()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<=', now()->addDays(3)->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        foreach ($instances as $instance) {
            $this->processInstance($instance, $subscription);
        }

        $subscription->refresh();

        if ($subscription->isActive()) {
            $subscription->calculateNextBookingDate();
            $subscription->refresh();
            $this->createInstancesForNext7Days($subscription);
        }
    }

    protected function processInstance(BookingInstance $instance, Subscription $subscription): void
    {
        if (! $this->isSlotAvailable($instance, $subscription)) {
            $instance->skip('venue_unavailable');

            return;
        }

        if ($subscription->auto_pay && ! $this->attemptPayment($instance, $subscription)) {
            return;
        }

        $booking = $instance->createBooking();

        if ($booking) {
            $subscription->increment('total_bookings_created');
        }
    }

    protected function isSlotAvailable(BookingInstance $instance, Subscription $subscription): bool
    {
        try {
            $result = $this->availabilityService->check(
                $subscription->venue_id,
                $instance->scheduled_date->toDateString(),
                substr((string) $instance->scheduled_start_time, 0, 5),
                ((int) $subscription->duration_hours) * 60,
            );

            return $result->available ?? false;
        } catch (\Throwable $e) {
            Log::warning('Subscription availability check failed', [
                'instance_id' => $instance->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function attemptPayment(BookingInstance $instance, Subscription $subscription): bool
    {
        $instance->increment('payment_attempts');
        $instance->update(['last_payment_attempt_at' => now()]);

        // TODO: integrate wallet/saved-payment-method charging.
        // For now, mark as completed so the booking can be created.
        $instance->update(['payment_status' => 'completed']);

        return true;
    }
}
