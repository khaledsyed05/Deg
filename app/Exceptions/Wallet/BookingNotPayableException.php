<?php

namespace App\Exceptions\Wallet;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown by PayBookingService when the booking is in a state
 * that disallows payment (already paid, cancelled, expired, etc.)
 * or when the request amount diverges from the booking total.
 *
 * Rendered as 409 Conflict via bootstrap/app.php's
 * HttpExceptionInterface clause.
 */
class BookingNotPayableException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(409, $message);
    }
}
