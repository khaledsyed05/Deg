<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'role' => TeamRole::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function accept(): void
    {
        $this->update(['status' => 'active', 'joined_at' => now()]);
        $this->team->increment('total_members');
    }

    public function decline(): void
    {
        $this->delete();
    }

    public function leave(): void
    {
        $this->update(['status' => 'left', 'left_at' => now()]);
        $this->team->decrement('total_members');
    }
}
