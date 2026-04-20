<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\DayType;
use Database\Factories\VenuePricingTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

class VenuePricingTier extends Model implements Sortable
{
    /** @use HasFactory<VenuePricingTierFactory> */
    use HasFactory, HasTranslations, SortableTrait;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /** @var array<string, mixed> */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected function casts(): array
    {
        return [
            'day_type' => DayType::class,
            'specific_day' => DayOfWeek::class,
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
