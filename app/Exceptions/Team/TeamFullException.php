<?php

namespace App\Exceptions\Team;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TeamFullException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(422, $message ?? __('team.team_full'));
    }
}
