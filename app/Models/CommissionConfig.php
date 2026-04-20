<?php

namespace App\Models;

use App\Enums\CommissionScope;
use App\Enums\CommissionType;
use Database\Factories\CommissionConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionConfig extends Model
{
    /** @use HasFactory<CommissionConfigFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scope' => CommissionScope::class,
            'commission_type' => CommissionType::class,
            'is_active' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
