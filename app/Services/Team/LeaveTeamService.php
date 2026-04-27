<?php

namespace App\Services\Team;

use App\Exceptions\Team\TeamException;
use App\Models\BookingPaymentSplit;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveTeamService
{
    public function leave(int $teamId, User $user, ?string $reason = null): void
    {
        $membership = TeamMember::where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            throw new TeamException('أنت لست عضواً في هذا الفريق', 404);
        }

        $roleValue = $membership->role instanceof \BackedEnum ? $membership->role->value : (string) $membership->role;

        if (in_array($roleValue, ['captain', 'admin'], true)) {
            $otherAdmins = TeamMember::where('team_id', $teamId)
                ->where('user_id', '!=', $user->id)
                ->where('status', 'active')
                ->whereIn('role', ['captain', 'admin'])
                ->count();

            if ($otherAdmins === 0) {
                throw new TeamException(
                    'أنت المدير الوحيد. يجب نقل الملكية لعضو آخر قبل المغادرة'
                );
            }
        }

        $pendingPayments = BookingPaymentSplit::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('booking', fn ($q) => $q->where('team_id', $teamId))
            ->count();

        if ($pendingPayments > 0) {
            throw new TeamException(
                "لديك {$pendingPayments} دفعة معلقة في هذا الفريق. يرجى تسويتها قبل المغادرة"
            );
        }

        DB::transaction(function () use ($membership, $teamId, $user, $reason) {
            $membership->update([
                'status' => 'left',
                'left_at' => now(),
            ]);

            DB::table('teams')->where('id', $teamId)->decrement('total_members');

            Log::channel('groups')->info('User left team', [
                'team_id' => $teamId,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);
        });
    }
}
