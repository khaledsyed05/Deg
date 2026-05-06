<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->activeParticipants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }

    /**
     * Pusher private-channel name following the spec convention:
     *
     *   private-dm-{u1}-{u2}      (u1 < u2)
     *   private-team-{team_id}
     *   private-group-{booking_id}
     */
    public function channelName(): string
    {
        return match ($this->type) {
            'team' => "private-team-{$this->reference_id}",
            'group' => "private-group-{$this->reference_id}",
            'dm' => $this->dmChannelName(),
            default => "private-conversation-{$this->id}",
        };
    }

    private function dmChannelName(): string
    {
        $userIds = $this->participants()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->take(2)
            ->all();

        if (count($userIds) < 2) {
            return "private-conversation-{$this->id}";
        }

        return "private-dm-{$userIds[0]}-{$userIds[1]}";
    }
}
