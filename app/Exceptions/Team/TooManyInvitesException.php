<?php

namespace App\Exceptions\Team;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TooManyInvitesException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(422, $message ?? __('team.too_many_invites'));
    }
}
