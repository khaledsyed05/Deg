<?php

namespace App\Exceptions\Review;

use RuntimeException;

class ReviewException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
