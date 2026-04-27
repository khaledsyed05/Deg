<?php

namespace App\Http\Resources\V1\Promotion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'type' => $this->type,
            'value' => (int) $this->value,
            'min_amount' => $this->min_amount !== null ? (int) $this->min_amount : null,
            'max_discount' => $this->max_discount !== null ? (int) $this->max_discount : null,
            'venue' => $this->whenLoaded('venue', fn () => $this->venue ? [
                'id' => $this->venue->id,
                'slug' => $this->venue->slug,
                'name' => $this->venue->getTranslations('name'),
            ] : null),
            'applies_to' => $this->applies_to,
            'is_featured' => (bool) $this->is_featured,
            'first_booking_only' => (bool) $this->first_booking_only,
            'allowed_days' => $this->allowed_days,
            'image_url' => $this->image_url,
            'valid_from' => $this->valid_from?->toISOString(),
            'valid_to' => $this->valid_to?->toISOString(),
            'max_uses' => $this->max_uses,
            'current_uses' => (int) $this->current_uses,
            'max_uses_per_user' => $this->max_uses_per_user,
            'status' => $this->status,
            'is_active' => $this->isActive(),
        ];
    }
}
