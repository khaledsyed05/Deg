<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'settled_at' => 'datetime',
            'notes_updated_at' => 'datetime',
        ];
    }

    public function notesUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notes_updated_by');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }
}
