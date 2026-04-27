<?php

namespace App\Http\Resources\V1\Venue;

use App\Http\Resources\VenueResource;
use Illuminate\Http\Request;

class VenueDetailResource extends VenueResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $settings = $this->club?->settings ?? [];
        $bookingRules = $settings['booking_rules'] ?? [];

        return array_merge($base, [
            'amenities' => $this->amenities ?? [],
            'opening_hours' => $this->opening_hours ?? [],
            'capacity' => $this->capacity,
            'size' => $this->size,
            'images' => $this->getMedia('images')->map(fn ($m) => [
                'url' => $m->getUrl(),
                'thumb_url' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
            ])->values(),
            'club_contact' => $this->club ? [
                'phone' => $this->club->phone_number,
                'whatsapp' => $this->club->whatsapp_number,
                'email' => $this->club->email,
                'address' => $this->club->address,
                'logo_url' => $this->club->hasMedia('logo') ? $this->club->getFirstMediaUrl('logo') : null,
            ] : null,
            'booking_rules' => [
                'min_hours' => $bookingRules['min_hours'] ?? 1,
                'max_hours' => $bookingRules['max_hours'] ?? 8,
                'deposit_required' => (bool) ($bookingRules['require_deposit'] ?? false),
                'deposit_percentage' => $bookingRules['deposit_percentage'] ?? null,
                'free_cancellation_hours' => $bookingRules['cancellation_hours'] ?? 24,
            ],
        ]);
    }
}
