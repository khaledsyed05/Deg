<?php

namespace App\Observers;

use App\Models\VenuePricingTier;
use Illuminate\Support\Facades\Cache;

class VenuePricingTierObserver
{
    public function saved(VenuePricingTier $tier): void
    {
        Cache::forget("venue:{$tier->venue_id}:pricing");
    }

    public function deleted(VenuePricingTier $tier): void
    {
        Cache::forget("venue:{$tier->venue_id}:pricing");
    }
}
