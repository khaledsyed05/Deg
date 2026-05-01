<?php

namespace App\Services\Team;

use App\Jobs\Notification\TeamInvitationNotificationJob;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

class TeamService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Team
    {
        if (Team::where('captain_id', $user->id)->count() >= 5) {
            throw new \DomainException('لا يمكنك إنشاء أكثر من 5 فرق');
        }

        if (Team::where('captain_id', $user->id)->where('name', $data['name'])->exists()) {
            throw new \DomainException('لديك فريق بنفس الاسم');
        }

        $team = Team::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'casual',
            'sport_category_id' => $data['sport_category_id'],
            'captain_id' => $user->id,
            'max_members' => $data['max_members'] ?? 20,
            'is_public' => $data['is_public'] ?? false,
            'requires_approval' => $data['requires_approval'] ?? false,
            'regular_schedule' => $data['regular_schedule'] ?? null,
        ]);

        try {
            activity()
                ->causedBy($user)
                ->performedOn($team)
                ->event('team_created')
                ->log('User created team');
        } catch (\Throwable $e) {
            // Activity log is optional.
        }

        return $team->fresh();
    }

    /**
     * @return array{success: bool, message: string, member?: TeamMember}
     */
    public function inviteMember(Team $team, int $userId, User $inviter): array
    {
        if (! $team->canInvite($inviter->id)) {
            return ['success' => false, 'message' => 'ليس لديك صلاحية دعوة أعضاء'];
        }

        if ($team->isFull()) {
            return ['success' => false, 'message' => 'الفريق ممتلئ'];
        }

        if ($team->hasMember($userId)) {
            return ['success' => false, 'message' => 'المستخدم عضو بالفعل'];
        }

        $member = $team->inviteMember($userId, $inviter->id);

        TeamInvitationNotificationJob::dispatch($team, $userId, $inviter->id);

        return ['success' => true, 'message' => 'تم إرسال الدعوة', 'member' => $member];
    }
}
