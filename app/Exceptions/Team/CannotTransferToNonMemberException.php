<?php

namespace App\Exceptions\Team;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CannotTransferToNonMemberException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(422, $message ?? __('team.cannot_transfer_to_non_member'));
    }
}
