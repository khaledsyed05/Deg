<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class VenueCategory extends Model implements Sortable
{
    use HasFactory, HasSlug, HasTranslations, SortableTrait;

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
            'is_active' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (VenueCategory $model) => $model->getTranslation('name', 'en'))
            ->saveSlugsTo('slug');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'category_id');
    }
}
