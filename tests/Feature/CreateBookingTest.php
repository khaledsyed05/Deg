<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/v1/bookings', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_missing_venue_id_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['venue_id']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_id']);
    }

    public function test_nonexistent_venue_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload(['venue_id' => 99999]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['venue_id']);
    }

    public function test_missing_booking_date_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['booking_date']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking_date']);
    }

    public function test_past_booking_date_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'booking_date' => now()->subDay()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking_date']);
    }

    public function test_missing_start_time_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['start_time']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_invalid_duration_not_multiple_of_30_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload(['duration_minutes' => 45]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_missing_payment_mode_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['payment_mode']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_mode']);
    }

    public function test_invalid_payment_mode_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload(['payment_mode' => 'installment']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_mode']);
    }

    public function test_missing_payment_provider_returns_422(): void
    {
        $user    = User::factory()->create();
        $payload = $this->validPayload();
        unset($payload['payment_provider']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_provider']);
    }

    public function test_invalid_payment_provider_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload(['payment_provider' => 'bitcoin']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_provider']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'venue_id'         => $this->venue->id,
            'booking_date'     => now()->addDays(3)->toDateString(),
            'start_time'       => '18:00',
            'duration_minutes' => 60,
            'payment_mode'     => 'full',
            'payment_provider' => 'syriatel_cash',
        ], $overrides);
    }
}
