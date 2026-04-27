<?php

namespace App\Exceptions\Booking;

use RuntimeException;

class RefundException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
