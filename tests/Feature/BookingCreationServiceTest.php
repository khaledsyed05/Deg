<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingCreationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->venue = Venue::factory()->create(['status' => 'active']);
    }

    public function test_deposit_amount_required_when_payment_mode_is_deposit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'payment_mode'   => 'deposit',
                'deposit_amount' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deposit_amount']);
    }

    public function test_notes_too_long_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'notes' => str_repeat('a', 501),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notes']);
    }

    public function test_duration_below_minimum_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'duration_minutes' => 15,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_duration_above_maximum_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'duration_minutes' => 270,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_valid_payment_providers_are_accepted(): void
    {
        $user = User::factory()->create();

        foreach (['syriatel_cash', 'mtn_cash', 'fatora', 'sama_pay', 'wallet'] as $provider) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/bookings', $this->validPayload([
                    'payment_provider' => $provider,
                ]));

            $response->assertJsonMissingValidationErrors(['payment_provider']);
        }
    }

    public function test_invalid_payment_provider_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'payment_provider' => 'cash_on_delivery',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_provider']);
    }

    public function test_start_time_must_match_format_hi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', $this->validPayload([
                'start_time' => '6pm',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
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
