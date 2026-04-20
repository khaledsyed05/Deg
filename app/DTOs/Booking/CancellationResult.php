<?php

namespace App\DTOs\Booking;

use App\Models\Booking;

readonly class CancellationResult
{
    public function __construct(
        public Booking $booking,
        public int $refundAmount,
        public bool $walletCredited,
    ) {}
}
