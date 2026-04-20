<?php

namespace App\Observers;

use App\Models\User;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(
        private WalletRepositoryInterface $walletRepo,
    ) {}

    public function created(User $user): void
    {
        try {
            $this->walletRepo->createForUser($user->id);
        } catch (\Throwable $e) {
            Log::error('UserObserver: failed to create wallet for new user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
