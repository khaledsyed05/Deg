<?php

namespace App\DTOs\Booking;

readonly class SlotAvailabilityResult
{
    public function __construct(
        public bool $available,
        public ?string $unavailableReason = null,
    ) {}
}
