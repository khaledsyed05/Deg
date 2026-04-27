<?php

namespace App\Exceptions\Team;

use RuntimeException;

class TeamException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
