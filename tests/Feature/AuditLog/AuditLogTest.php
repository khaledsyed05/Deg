<?php

namespace Tests\Feature\AuditLog;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Venue;
use App\Models\Wallet;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Sprint 8 B2 — verify mobile-facing financial / authorization
 * mutations record an AuditLog row.
 *
 * The AuditLog model + table existed pre-Sprint-8 (used only by
 * the admin panel). Sprint 8 wires it into the mobile API layer.
 */
class AuditLogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pay_booking_records_audit_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');
        Wallet::forUser($user)->update(['balance' => 100000]);

        $booking = Booking::factory()->pendingPayment()->create([
            'user_id' => $user->id,
            'venue_id' => Venue::factory()->create()->id,
            'venue_price' => 50000,
            'total_price' => 50000,
            'club_payout_amount' => 50000,
        ]);

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/wallet/pay-booking', [
            'booking_id' => $booking->id,
            'idempotency_key' => 'audit-pay-001',
            'amount' => 50000,
        ])->assertOk();

        $log = AuditLog::query()
            ->where('action', 'wallet.pay_booking')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(Booking::class, $log->subject_type);
        $this->assertSame($booking->id, (int) $log->subject_id);
        $this->assertSame(50000, $log->changes['amount']);
        $this->assertSame('audit-pay-001', $log->changes['idempotency_key']);
    }

    public function test_team_kick_records_audit_log(): void
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $member = User::factory()->create();
        $member->assignRole('player');

        $team = Team::factory()->withCaptain($captain)->create();
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($captain, 'sanctum');

        $this->postJson("/api/v1/teams/{$team->id}/kick", [
            'member_id' => $member->id,
        ])->assertOk();

        $log = AuditLog::query()
            ->where('action', 'team.kick')
            ->where('user_id', $captain->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(Team::class, $log->subject_type);
        $this->assertSame($team->id, (int) $log->subject_id);
        $this->assertSame($member->id, $log->changes['kicked_user_id']);
    }

    public function test_team_transfer_captain_records_old_and_new(): void
    {
        $captain = User::factory()->create();
        $captain->assignRole('player');
        $newCaptain = User::factory()->create();
        $newCaptain->assignRole('player');

        $team = Team::factory()->withCaptain($captain)->create();
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $newCaptain->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($captain, 'sanctum');

        $this->postJson("/api/v1/teams/{$team->id}/transfer-captain", [
            'new_captain_id' => $newCaptain->id,
        ])->assertOk();

        $log = AuditLog::query()
            ->where('action', 'team.transfer_captain')
            ->where('user_id', $captain->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($captain->id, $log->changes['old_captain_id']);
        $this->assertSame($newCaptain->id, $log->changes['new_captain_id']);
    }

    public function test_auth_login_records_is_new_user_flag(): void
    {
        $this->mock(OtpService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '+963991234567',
            'otp' => '12345',
            'challenge_uuid' => '11111111-2222-3333-4444-555555555555',
        ])->assertOk();

        $log = AuditLog::query()->where('action', 'auth.login')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->changes['is_new_user']);
        $this->assertSame('otp', $log->changes['method']);
        $this->assertNotNull($log->ip_address);
    }

    public function test_wallet_settings_change_records_before_after(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');
        Wallet::forUser($user)->update(['settings' => ['low_balance_alert' => false]]);

        $this->actingAs($user, 'sanctum');

        $this->putJson('/api/v1/wallet/settings', [
            'low_balance_alert' => true,
        ])->assertOk();

        $log = AuditLog::query()
            ->where('action', 'wallet.settings_changed')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertFalse($log->changes['before']['low_balance_alert']);
        $this->assertTrue($log->changes['after']['low_balance_alert']);
    }

    public function test_audit_log_captures_ip_address(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');
        Wallet::forUser($user)->update(['balance' => 100000]);

        $booking = Booking::factory()->pendingPayment()->create([
            'user_id' => $user->id,
            'venue_id' => Venue::factory()->create()->id,
            'venue_price' => 50000,
            'total_price' => 50000,
            'club_payout_amount' => 50000,
        ]);

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/wallet/pay-booking', [
            'booking_id' => $booking->id,
            'idempotency_key' => 'audit-ip-001',
            'amount' => 50000,
        ])->assertOk();

        $log = AuditLog::query()->where('action', 'wallet.pay_booking')->latest('id')->first();

        $this->assertNotNull($log->ip_address);
        $this->assertNotEmpty($log->ip_address);
    }
}
