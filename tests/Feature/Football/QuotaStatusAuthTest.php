<?php

namespace Tests\Feature\Football;

use Tests\TestCase;

class QuotaStatusAuthTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/football/live/quota-status')
            ->assertStatus(401);
    }

    public function test_non_admin_user_gets_403(): void
    {
        $this->actingAsPlayer();

        $this->getJson('/api/v1/football/live/quota-status')
            ->assertStatus(403);
    }

    public function test_admin_can_view_quota_status(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/football/live/quota-status')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['keys', 'has_keys']]);
    }
}
