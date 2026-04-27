<?php

namespace App\Exceptions\Promotion;

use RuntimeException;

class PromotionException extends RuntimeException
{
    public function __construct(string $message = '', public int $statusCode = 422)
    {
        parent::__construct($message);
    }
}
