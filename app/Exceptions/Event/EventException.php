<?php

namespace App\Exceptions\Event;

use RuntimeException;

class EventException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
