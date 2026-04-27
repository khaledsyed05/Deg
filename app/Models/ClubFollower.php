<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubFollower extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'notify_updates' => 'boolean',
            'notify_events' => 'boolean',
            'notify_promotions' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
