<?php

namespace App\Services\Club;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Repositories\Contracts\ClubRepositoryInterface;

class ClubOnboardingService
{
    public function __construct(
        private ClubRepositoryInterface $clubRepo,
    ) {}

    public function register(int $ownerId, array $data): Club
    {
        return $this->clubRepo->create([
            'owner_id' => $ownerId,
            'city_id' => $data['city_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'amenities' => $data['amenities'] ?? null,
            'status' => ClubStatus::PendingApproval,
        ]);
    }

    public function update(Club $club, array $data): Club
    {
        return $this->clubRepo->update($club, $data);
    }
}
