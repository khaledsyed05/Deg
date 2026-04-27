<?php

namespace App\Services\Venue;

use App\Models\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VenueService
{
    public function __construct(
        private VenueRepositoryInterface $venues,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVenue(array $data): Venue
    {
        return DB::transaction(function () use ($data) {
            $photos = $this->pullList($data, 'photos', UploadedFile::class);
            $deletePhotoIds = $this->pullList($data, 'delete_photo_ids');
            $photoOrder = $this->pullList($data, 'photo_order');
            $coverPhotoId = $data['cover_photo_id'] ?? null;
            unset($data['cover_photo_id'], $data['pricing_tiers_provided']);
            $sportIds = $this->pullList($data, 'sport_ids');
            $pricingTiers = $this->pullList($data, 'pricing_tiers');

            $venue = $this->venues->create($data);

            $this->handlePhotoUploads($venue, $photos, $deletePhotoIds, $photoOrder, $coverPhotoId);
            $this->syncSports($venue, $sportIds);
            $this->syncPricingTiers($venue, $pricingTiers);

            return $venue->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVenue(Venue $venue, array $data): Venue
    {
        return DB::transaction(function () use ($venue, $data) {
            $photos = $this->pullList($data, 'photos', UploadedFile::class);
            $deletePhotoIds = $this->pullList($data, 'delete_photo_ids');
            $photoOrder = $this->pullList($data, 'photo_order');
            $coverPhotoId = $data['cover_photo_id'] ?? null;
            unset($data['cover_photo_id']);
            $sportIds = $this->pullList($data, 'sport_ids');
            $pricingTiers = $this->pullList($data, 'pricing_tiers');
            $hasPricingTiersKey = array_key_exists('pricing_tiers_provided', $data);
            unset($data['pricing_tiers_provided']);

            $updated = $this->venues->update($venue, $data);

            $this->handlePhotoUploads($updated, $photos, $deletePhotoIds, $photoOrder, $coverPhotoId);
            $this->syncSports($updated, $sportIds);
            // Only touch pricing tiers if caller explicitly provided them.
            if ($hasPricingTiersKey) {
                $this->syncPricingTiers($updated, $pricingTiers);
            }

            return $updated->fresh();
        });
    }

    /**
     * Occupancy rate over the last 30 days, relative to the venue's actual
     * operating minutes derived from opening_hours.
     */
    public function calculateOccupancyRate(Venue $venue): float
    {
        $opening = is_array($venue->opening_hours) ? $venue->opening_hours : [];

        // Sum weekly operating minutes across all non-closed days.
        $weeklyMinutes = 0;
        foreach ($opening as $day) {
            if (($day['closed'] ?? false) === true) {
                continue;
            }
            $open = $day['open'] ?? null;
            $close = $day['close'] ?? null;
            if (! $open || ! $close) {
                continue;
            }
            try {
                $o = CarbonImmutable::createFromFormat('H:i', substr($open, 0, 5));
                $c = CarbonImmutable::createFromFormat('H:i', substr($close, 0, 5));
                if (! $o || ! $c) {
                    continue;
                }
                $minutes = $c->greaterThan($o) ? $c->diffInMinutes($o) : 0;
                $weeklyMinutes += $minutes;
            } catch (\Throwable) {
                continue;
            }
        }

        // 30 days ≈ 30/7 weeks.
        $availableMinutes = (int) round($weeklyMinutes * (30 / 7));
        if ($availableMinutes <= 0) {
            return 0.0;
        }

        $since = now()->subDays(30);
        $bookedMinutes = (int) $venue->bookings()
            ->where('starts_at', '>=', $since)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('duration_minutes');

        return round(min(100, ($bookedMinutes / $availableMinutes) * 100), 1);
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, int|string>  $deleteIds
     * @param  array<int, int|string>  $orderedIds
     */
    private function handlePhotoUploads(
        Venue $venue,
        array $photos,
        array $deleteIds,
        array $orderedIds,
        int|string|null $coverPhotoId,
    ): void {
        foreach ($deleteIds as $mediaId) {
            $media = $venue->getMedia('images')->firstWhere('id', (int) $mediaId);
            $media?->delete();
        }

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $venue->addMedia($photo)->toMediaCollection('images');
            }
        }

        // Refresh media to include new uploads.
        $current = $venue->getMedia('images')->keyBy('id');

        // If the user chose a cover from existing media, put it first.
        $coverId = $coverPhotoId !== null ? (int) $coverPhotoId : null;
        $finalOrder = [];
        if ($coverId !== null && isset($current[$coverId])) {
            $finalOrder[] = $coverId;
        }
        foreach ($orderedIds as $id) {
            $intId = (int) $id;
            if ($intId === $coverId) {
                continue;
            }
            if (isset($current[$intId])) {
                $finalOrder[] = $intId;
            }
        }
        // Append any media the client didn't know about (newly uploaded).
        foreach ($current as $id => $_media) {
            if (! in_array($id, $finalOrder, true)) {
                $finalOrder[] = $id;
            }
        }

        foreach ($finalOrder as $position => $id) {
            $media = $current[$id] ?? null;
            if ($media) {
                $media->order_column = $position;
                $media->saveQuietly();
            }
        }
    }

    /**
     * @param  array<int, int|string>  $sportIds
     */
    private function syncSports(Venue $venue, array $sportIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $sportIds)));
        $venue->sportCategories()->sync($ids);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     */
    private function syncPricingTiers(Venue $venue, array $tiers): void
    {
        $venue->pricingTiers()->delete();

        foreach (array_values($tiers) as $index => $tier) {
            if (! is_array($tier)) {
                continue;
            }
            $venue->pricingTiers()->create([
                'name' => [
                    'ar' => $tier['name']['ar'] ?? '',
                    'en' => $tier['name']['en'] ?? '',
                ],
                'day_type' => $tier['day_type'] ?? 'all_days',
                'specific_day' => $tier['day_type'] === 'specific_day' ? ($tier['specific_day'] ?? null) : null,
                'start_time' => $tier['start_time'] ?? '08:00',
                'end_time' => $tier['end_time'] ?? '23:00',
                'duration_minutes' => (int) ($tier['duration_minutes'] ?? 60),
                'price' => (int) ($tier['price'] ?? 0),
                'is_active' => (bool) ($tier['is_active'] ?? true),
                'order_column' => $index,
            ]);
        }
    }

    /**
     * Pull a key out of $data, returning a cleanly-typed array.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, mixed>
     */
    private function pullList(array &$data, string $key, ?string $instanceof = null): array
    {
        $value = $data[$key] ?? [];
        unset($data[$key]);

        if (! is_array($value)) {
            return [];
        }

        if ($instanceof !== null) {
            return array_values(array_filter($value, fn ($v) => $v instanceof $instanceof));
        }

        return array_values($value);
    }
}
