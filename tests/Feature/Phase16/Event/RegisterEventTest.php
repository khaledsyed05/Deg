<?php

namespace Tests\Feature\Phase16\Event;

use App\Enums\CreditType;
use App\Models\Event;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterEventTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_cannot_register(): void
    {
        $event = Event::factory()->create();
        $this->postJson("/api/v1/events/{$event->id}/register")
            ->assertUnauthorized();
    }

    public function test_user_can_register_for_free_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.amount_paid', 0);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        $this->assertSame(1, (int) $event->fresh()->current_participants);
    }

    public function test_paid_event_debits_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::forUser($user);
        $wallet->credit(100_000, CreditType::TOPUP, 'seed');

        $event = Event::factory()->paid(25_000)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertSame(75_000, (int) $wallet->fresh()->balance);
    }

    public function test_registration_blocked_when_insufficient_balance(): void
    {
        $user = User::factory()->create();
        Wallet::forUser($user); // zero balance

        $event = Event::factory()->paid(25_000)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertStatus(402);

        $this->assertDatabaseMissing('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);
        $this->assertSame(0, (int) $event->fresh()->current_participants);
    }

    public function test_full_event_rejects_registration(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->full()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertStatus(422);
    }

    public function test_double_registration_blocked(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertStatus(422);
    }

    public function test_cancellation_within_24h_rejected(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'starts_at' => now()->addHours(12),
            'registration_closes_at' => now()->addHours(6),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/events/{$event->id}/registration")
            ->assertStatus(422);
    }

    public function test_cancellation_refunds_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::forUser($user);
        $wallet->credit(100_000, CreditType::TOPUP, 'seed');

        $event = Event::factory()->paid(25_000)->create([
            'starts_at' => now()->addDays(3),
            'registration_closes_at' => now()->addDays(2),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated();

        $this->assertSame(75_000, (int) $wallet->fresh()->balance);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/events/{$event->id}/registration")
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.refunded_amount', 25000);

        $this->assertSame(100_000, (int) $wallet->fresh()->balance);
        $this->assertSame(0, (int) $event->fresh()->current_participants);
    }
}
