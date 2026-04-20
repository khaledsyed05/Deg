<?php

namespace App\Models;

use App\Enums\ClubStatus;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Club extends Model implements HasMedia
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory, HasSlug, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'status' => ClubStatus::class,
            'is_featured' => 'boolean',
            'avg_rating' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (Club $model) => $model->getTranslation('name', 'en'))
            ->saveSlugsTo('slug');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function commissionConfigs(): HasMany
    {
        return $this->hasMany(CommissionConfig::class);
    }
}
