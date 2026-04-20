<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Club;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenuePricingTier;
use App\Models\VenueWaitlist;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ModelFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertModelExists($user);
        $this->assertNotNull($user->phone_number);
        $this->assertSame('active', $user->account_status->value);
    }

    public function test_club_factory_creates_valid_club(): void
    {
        $club = Club::factory()->create();

        $this->assertModelExists($club);
        $this->assertNotNull($club->name);
    }

    public function test_venue_factory_creates_valid_venue(): void
    {
        $venue = Venue::factory()->create();

        $this->assertModelExists($venue);
        $this->assertNotNull($venue->club_id);
    }

    public function test_venue_pricing_tier_factory_creates_valid_tier(): void
    {
        $tier = VenuePricingTier::factory()->create();

        $this->assertModelExists($tier);
        $this->assertGreaterThan(0, $tier->price);
    }

    public function test_booking_factory_creates_valid_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->assertModelExists($booking);
        $this->assertInstanceOf(BookingStatus::class, $booking->status);
    }

    public function test_venue_waitlist_factory_creates_valid_entry(): void
    {
        $entry = VenueWaitlist::factory()->create();

        $this->assertModelExists($entry);
    }

    public function test_booking_status_cast_returns_enum(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_user_has_many_bookings(): void
    {
        $user = User::factory()->create();
        Booking::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->bookings);
        $this->assertInstanceOf(Booking::class, $user->bookings->first());
    }

    public function test_venue_belongs_to_club(): void
    {
        $club  = Club::factory()->create();
        $venue = Venue::factory()->for($club)->create();

        $this->assertTrue($venue->club->is($club));
    }

    public function test_booking_belongs_to_user(): void
    {
        $user    = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($booking->user->is($user));
    }

    public function test_booking_belongs_to_venue(): void
    {
        $venue   = Venue::factory()->create();
        $booking = Booking::factory()->create(['venue_id' => $venue->id]);

        $this->assertTrue($booking->venue->is($venue));
    }

    public function test_venue_has_many_pricing_tiers(): void
    {
        $venue = Venue::factory()->create();
        VenuePricingTier::factory()->count(2)->create(['venue_id' => $venue->id]);

        $this->assertCount(2, $venue->pricingTiers);
    }
}
