<?php

namespace Tests\Feature\MobileEnvelope;

use Tests\MobileIntegrationTest;

/**
 * Phase 7 — Tournaments + Notifications — 10 covered endpoints
 * (drop the 1 FCM gap entry).
 *
 * Tournaments (events):
 * - GET /events
 * - GET /events/{id}
 * - POST /events/{id}/register
 * - GET /events/registered
 * - DELETE /events/{id}/registration
 *
 * Notifications:
 * - GET /notifications
 * - GET /notifications/unread
 * - PUT /notifications/{id}/read
 * - PUT /notifications/read-all
 * - DELETE /notifications/{id}
 */
class Phase07TournamentsNotificationsTest extends MobileIntegrationTest
{
    public function test_get_events_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertOk();
        $this->assertEnvelope($response);
    }

    public function test_get_event_show_returns_envelope(): void
    {
        $response = $this->getJson('/api/v1/events/1');

        $this->assertEnvelope($response);
    }

    public function test_post_event_register_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->postJson('/api/v1/events/1/register', []);

        $this->assertEnvelope($response);
    }

    public function test_get_events_registered_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/events/registered');

        $this->assertEnvelope($response);
    }

    public function test_delete_event_registration_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->deleteJson('/api/v1/events/1/registration');

        $this->assertEnvelope($response);
    }

    public function test_get_notifications_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/notifications');

        $this->assertEnvelope($response);
    }

    public function test_get_notifications_unread_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->getJson('/api/v1/notifications/unread');

        $this->assertEnvelope($response);
    }

    public function test_put_notification_read_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->putJson('/api/v1/notifications/1/read');

        $this->assertEnvelope($response);
    }

    public function test_put_notifications_read_all_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->putJson('/api/v1/notifications/read-all');

        $this->assertEnvelope($response);
    }

    public function test_delete_notification_returns_envelope(): void
    {
        $this->actingAsRole('player');

        $response = $this->deleteJson('/api/v1/notifications/1');

        $this->assertEnvelope($response);
    }
}
