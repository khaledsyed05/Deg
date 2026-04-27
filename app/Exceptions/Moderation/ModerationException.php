<?php

namespace App\Exceptions\Moderation;

use RuntimeException;

class ModerationException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
