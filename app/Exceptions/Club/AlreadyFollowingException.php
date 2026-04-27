<?php

namespace App\Exceptions\Club;

use RuntimeException;

class AlreadyFollowingException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
