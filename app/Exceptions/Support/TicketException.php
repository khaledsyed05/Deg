<?php

namespace App\Exceptions\Support;

use RuntimeException;

class TicketException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
