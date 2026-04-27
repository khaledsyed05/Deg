<?php

namespace App\Observers;

use App\Enums\AchievementType;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralObserver
{
    public function updated(Referral $referral): void
    {
        if (! $referral->wasChanged('status')) {
            return;
        }

        if ($referral->status !== 'completed' && $referral->status !== 'rewarded') {
            return;
        }

        if (! $referral->referrer) {
            return;
        }

        $this->checkSocialButterfly($referral->referrer);
    }

    protected function checkSocialButterfly(User $user): void
    {
        try {
            $count = (int) $user->referrals()
                ->whereIn('status', ['completed', 'rewarded'])
                ->count();

            $achievement = $user->achievements()->firstOrCreate(
                ['type' => AchievementType::SocialButterfly->value],
                ['progress' => 0, 'target' => 5],
            );

            $achievement->progress = $count;
            $achievement->save();

            if ($count >= 5 && ! $achievement->isUnlocked()) {
                $achievement->unlock();
            }
        } catch (\Throwable $e) {
            Log::error('ReferralObserver: failed to check social butterfly', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
