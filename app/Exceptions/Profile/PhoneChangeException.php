<?php

namespace App\Exceptions\Profile;

use RuntimeException;

class PhoneChangeException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
