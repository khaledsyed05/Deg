<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'allowed_ips' => 'array',
        ];
    }

    public static function current(): ?self
    {
        return self::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->latest()
            ->first();
    }
}
