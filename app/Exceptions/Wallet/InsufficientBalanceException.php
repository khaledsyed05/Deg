<?php

namespace App\Exceptions\Wallet;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown by PayBookingService when wallet.available < amount.
 *
 * Extends Symfony's HttpException so the bootstrap/app.php
 * HttpExceptionInterface handler renders it as the canonical
 * 422 envelope without an extra clause.
 */
class InsufficientBalanceException extends HttpException
{
    public function __construct(
        string $message,
        public readonly int $available = 0,
        public readonly int $required = 0,
    ) {
        parent::__construct(422, $message);
    }
}
