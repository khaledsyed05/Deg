<?php

namespace App\Models;

use Database\Factories\VenueWaitlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueWaitlist extends Model
{
    /** @use HasFactory<VenueWaitlistFactory> */
    use HasFactory;

    protected $table = 'venue_waitlist';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'notified_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
