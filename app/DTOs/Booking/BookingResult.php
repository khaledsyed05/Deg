<?php

namespace App\DTOs\Booking;

use App\DTOs\Payment\CommissionResult;
use App\Models\Booking;

readonly class BookingResult
{
    public function __construct(
        public Booking $booking,
        public CommissionResult $commission,
    ) {}
}
