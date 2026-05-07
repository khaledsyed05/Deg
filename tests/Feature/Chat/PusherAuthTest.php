<?php

namespace Tests\Feature\Chat;

use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Security-critical coverage for POST /pusher/auth.
 *
 * Channel hijacking is the primary threat model: a user with a
 * valid Sanctum token tries to subscribe to someone else's
 * conversation. Every test in this file exists to make hijacking
 * harder than the legitimate happy paths.
 */
class PusherAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function postAuth(array $body): TestResponse
    {
        return $this->postJson('/api/v1/pusher/auth', $body + [
            'socket_id' => '12345.67890',
        ]);
    }

    // =================================================================
    // DM channels (private-dm-{u1}-{u2})
    // =================================================================

    public function test_dm_happy_path_user_is_u1(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        [$lo, $hi] = $u1->id < $u2->id ? [$u1->id, $u2->id] : [$u2->id, $u1->id];

        $this->actingAs(User::find($lo), 'sanctum');

        $response = $this->postAuth(['channel_name' => "private-dm-{$lo}-{$hi}"]);

        $response->assertOk()->assertJsonStructure(['data' => ['auth']]);
    }

    public function test_dm_happy_path_user_is_u2(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        [$lo, $hi] = $u1->id < $u2->id ? [$u1->id, $u2->id] : [$u2->id, $u1->id];

        $this->actingAs(User::find($hi), 'sanctum');

        $response = $this->postAuth(['channel_name' => "private-dm-{$lo}-{$hi}"]);

        $response->assertOk()->assertJsonStructure(['data' => ['auth']]);
    }

    public function test_dm_hijack_attempt_user_is_neither(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $hijacker = User::factory()->create();
        [$lo, $hi] = $u1->id < $u2->id ? [$u1->id, $u2->id] : [$u2->id, $u1->id];

        $this->actingAs($hijacker, 'sanctum');

        $response = $this->postAuth(['channel_name' => "private-dm-{$lo}-{$hi}"]);

        $response->assertForbidden();
    }

    public function test_dm_malformed_channel_name_no_second_user(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postAuth(['channel_name' => 'private-dm-abc'])->assertForbidden();
    }

    public function test_dm_malformed_channel_name_unsorted_or_zero(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        // u1 must be < u2 — equal or descending is not the canonical
        // form and is rejected to avoid u1 == u2 self-channel weirdness.
        $this->postAuth(['channel_name' => 'private-dm-5-5'])->assertForbidden();
        $this->postAuth(['channel_name' => 'private-dm-7-3'])->assertForbidden();
        $this->postAuth(['channel_name' => 'private-dm-0-1'])->assertForbidden();
    }

    // =================================================================
    // Team channels (private-team-{team_id})
    // =================================================================

    public function test_team_channel_happy_path_member(): void
    {
        $captain = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->withCaptain($captain)->create();
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($member, 'sanctum');

        $this->postAuth(['channel_name' => "private-team-{$team->id}"])
            ->assertOk()
            ->assertJsonStructure(['data' => ['auth']]);
    }

    public function test_team_channel_hijack_non_member(): void
    {
        $captain = User::factory()->create();
        $stranger = User::factory()->create();
        $team = Team::factory()->withCaptain($captain)->create();

        $this->actingAs($stranger, 'sanctum');

        $this->postAuth(['channel_name' => "private-team-{$team->id}"])
            ->assertForbidden();
    }

    public function test_team_channel_unknown_team(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postAuth(['channel_name' => 'private-team-999999'])
            ->assertForbidden();
    }

    // =================================================================
    // Group channels (private-group-{booking_id})
    // =================================================================

    public function test_group_channel_happy_path_booking_owner(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum');

        $this->postAuth(['channel_name' => "private-group-{$booking->id}"])
            ->assertOk()
            ->assertJsonStructure(['data' => ['auth']]);
    }

    public function test_group_channel_happy_path_booking_participant(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);
        BookingParticipant::create([
            'booking_id' => $booking->id,
            'user_id' => $participant->id,
            'status' => 'accepted',
            'payment_share' => 0,
            'payment_status' => 'pending',
        ]);

        $this->actingAs($participant, 'sanctum');

        $this->postAuth(['channel_name' => "private-group-{$booking->id}"])
            ->assertOk()
            ->assertJsonStructure(['data' => ['auth']]);
    }

    public function test_group_channel_hijack_non_participant(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger, 'sanctum');

        $this->postAuth(['channel_name' => "private-group-{$booking->id}"])
            ->assertForbidden();
    }

    public function test_group_channel_unknown_booking(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postAuth(['channel_name' => 'private-group-999999'])
            ->assertForbidden();
    }

    // =================================================================
    // Catch-all
    // =================================================================

    public function test_unknown_channel_pattern(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postAuth(['channel_name' => 'private-mystery-1'])->assertForbidden();
        $this->postAuth(['channel_name' => 'private-presence-1'])->assertForbidden();
    }

    public function test_public_channel_rejected(): void
    {
        // Sprint 7 doesn't issue public channels; if a client asks
        // for one, deny — the real-time tier shouldn't be a free
        // pubsub bus.
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postAuth(['channel_name' => 'public-broadcast'])->assertForbidden();
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->postAuth(['channel_name' => 'private-team-1'])->assertUnauthorized();
    }

    public function test_validation_rejects_missing_socket_id(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postJson('/api/v1/pusher/auth', ['channel_name' => 'private-team-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['socket_id']);
    }

    public function test_validation_rejects_missing_channel_name(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postJson('/api/v1/pusher/auth', ['socket_id' => '1.2'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['channel_name']);
    }

    public function test_concurrent_auth_requests_independent_signatures(): void
    {
        // Pusher signatures are stateless; two concurrent calls for
        // the same channel/socket pair must both succeed.
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        [$lo, $hi] = $u1->id < $u2->id ? [$u1->id, $u2->id] : [$u2->id, $u1->id];

        $this->actingAs(User::find($lo), 'sanctum');

        $first = $this->postAuth(['channel_name' => "private-dm-{$lo}-{$hi}"]);
        $second = $this->postAuth(['channel_name' => "private-dm-{$lo}-{$hi}"]);

        $first->assertOk();
        $second->assertOk();
        $this->assertNotEmpty($first->json('data.auth'));
        $this->assertNotEmpty($second->json('data.auth'));
    }
}
