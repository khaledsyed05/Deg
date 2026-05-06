<?php

namespace App\Exceptions\Team;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InviteExpiredException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(410, $message ?? __('team.invite_expired'));
    }
}
