<?php

namespace App\Services\Payment;

use App\Enums\DepositStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendWhatsAppNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    /**
     * Process a normalized callback result and update Payment + Booking state.
     *
     * @param  array{status: string, transaction_id: string, amount: float, reference?: string|null}  $normalized
     */
    public function process(array $normalized): void
    {
        $transactionId = $normalized['transaction_id'] ?? null;

        if (! $transactionId) {
            Log::warning('PaymentWebhookService: missing transaction_id', $normalized);

            return;
        }

        $payment = $this->paymentRepo->query()
            ->where('provider_transaction_id', $transactionId)
            ->first();

        if (! $payment) {
            Log::warning('PaymentWebhookService: payment not found', ['transaction_id' => $transactionId]);

            return;
        }

        $newStatus = PaymentStatus::from($normalized['status']);

        if ($payment->status === $newStatus) {
            return;
        }

        DB::transaction(function () use ($payment, $newStatus) {
            $updateData = ['status' => $newStatus];

            if ($newStatus === PaymentStatus::Completed) {
                $updateData['completed_at'] = now();
            } elseif ($newStatus === PaymentStatus::Failed || $newStatus === PaymentStatus::Cancelled) {
                $updateData['failed_at'] = now();
            }

            $this->paymentRepo->update($payment, $updateData);

            if ($newStatus === PaymentStatus::Completed) {
                $this->markBookingDepositPaid($payment->booking_id);
            }
        });

        if ($newStatus === PaymentStatus::Completed && $payment->booking_id) {
            $booking = $this->bookingRepo->find($payment->booking_id);
            if ($booking) {
                SendWhatsAppNotification::dispatch($booking, 'booking_confirmed');
            }
        }
    }

    private function markBookingDepositPaid(int $bookingId): void
    {
        $booking = $this->bookingRepo->find($bookingId);

        if (! $booking) {
            Log::warning('PaymentWebhookService: booking not found', ['booking_id' => $bookingId]);

            return;
        }

        if ($booking->deposit_status !== DepositStatus::Paid) {
            $this->bookingRepo->update($booking, ['deposit_status' => DepositStatus::Paid]);
        }
    }
}
