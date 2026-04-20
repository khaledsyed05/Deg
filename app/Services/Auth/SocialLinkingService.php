<?php

namespace App\Services\Auth;

use App\Models\SocialIdentity;
use App\Models\User;
use App\Repositories\Contracts\SocialIdentityRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SocialLinkingService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private SocialIdentityRepositoryInterface $socialRepo,
    ) {}

    /**
     * Link a Google account to an existing phone-authenticated user.
     *
     * @param  array{uid: string, email: ?string, name: ?string}  $firebaseClaims
     */
    public function linkGoogle(User $user, array $firebaseClaims): SocialIdentity
    {
        $existing = $this->socialRepo->findByProvider('google', $firebaseClaims['uid']);

        if ($existing && $existing->user_id !== $user->id) {
            throw new RuntimeException('This Google account is already linked to another user.');
        }

        if ($existing) {
            return $existing; // already linked
        }

        return DB::transaction(function () use ($user, $firebaseClaims) {
            $this->userRepo->update($user, [
                'firebase_uid' => $firebaseClaims['uid'],
                'firebase_provider' => 'google',
            ]);

            return $this->socialRepo->create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_uid' => $firebaseClaims['uid'],
                'provider_email' => $firebaseClaims['email'],
                'provider_meta' => ['name' => $firebaseClaims['name']],
            ]);
        });
    }

    public function unlink(User $user, string $provider): void
    {
        $identity = $this->socialRepo->findByProvider($provider, $user->firebase_uid ?? '');

        if (! $identity || $identity->user_id !== $user->id) {
            throw new RuntimeException("No linked {$provider} account found.");
        }

        $this->socialRepo->delete($identity);

        $this->userRepo->update($user, [
            'firebase_uid' => null,
            'firebase_provider' => null,
        ]);
    }
}
