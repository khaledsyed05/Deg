<?php

namespace App\DTOs\Auth;

use App\Models\User;

readonly class OtpVerificationResult
{
    public function __construct(
        public bool $verified,
        public ?User $user,
        public bool $isNewUser,
        public ?string $token = null,
    ) {}
}
