<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

class AppPlatform extends Model implements Sortable
{
    use HasTranslations, SortableTrait;

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
            'enabled' => 'boolean',
        ];
    }

    public function environments(): HasMany
    {
        return $this->hasMany(AppEnvironment::class);
    }
}
