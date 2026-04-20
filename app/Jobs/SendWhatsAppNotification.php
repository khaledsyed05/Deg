<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\Notification\WhatsAppMessageTemplates;
use App\Services\Notification\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [1, 5, 10];

    public function __construct(
        private Booking $booking,
        private string $messageType,
        private ?string $cancellationReason = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $clubId = $this->booking->venue->club_id;

        $status = $whatsapp->getSessionStatus($clubId);
        if (($status['status'] ?? '') !== 'connected') {
            return;
        }

        $phoneNumber = $this->booking->user->phone_number;
        if (! $phoneNumber) {
            return;
        }

        $message = $this->buildMessage();

        $whatsapp->sendMessage($clubId, $phoneNumber, $message);
    }

    private function buildMessage(): string
    {
        return match ($this->messageType) {
            'booking_confirmed' => WhatsAppMessageTemplates::bookingConfirmed($this->booking),
            'payment_reminder' => WhatsAppMessageTemplates::paymentReminder($this->booking),
            'booking_cancelled' => WhatsAppMessageTemplates::bookingCancelled($this->booking, $this->cancellationReason ?? ''),
            default => throw new \InvalidArgumentException("Unknown message type: {$this->messageType}"),
        };
    }
}
