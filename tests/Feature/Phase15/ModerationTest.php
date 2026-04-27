<?php

namespace Tests\Feature\Phase15;

use App\Models\Review;
use App\Models\User;
use App\Models\Venue;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    public function test_user_can_report_a_venue(): void
    {
        $this->actingAsPlayer();
        $venue = Venue::factory()->create();

        $this->postJson("/api/v1/venues/{$venue->slug}/report", [
            'reason' => 'fake_venue',
            'description' => 'الملعب غير موجود في الواقع',
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertEquals(1, $venue->fresh()->reports_count);
    }

    public function test_duplicate_venue_report_rejected(): void
    {
        $this->actingAsPlayer();
        $venue = Venue::factory()->create();

        $payload = ['reason' => 'fake_venue', 'description' => 'duplicate report test description'];
        $this->postJson("/api/v1/venues/{$venue->slug}/report", $payload)->assertStatus(201);
        $this->postJson("/api/v1/venues/{$venue->slug}/report", $payload)->assertStatus(422);
    }

    public function test_venue_auto_flag_after_threshold(): void
    {
        $venue = Venue::factory()->create();
        $threshold = (int) config('bookings.moderation.auto_flag_venue_threshold', 5);

        for ($i = 0; $i < $threshold; $i++) {
            $u = User::factory()->create();
            $u->assignRole('player');
            $this->actingAs($u, 'sanctum');
            $this->postJson("/api/v1/venues/{$venue->slug}/report", [
                'reason' => 'fake_venue',
                'description' => 'description '.$i.' for testing auto flag threshold',
            ])->assertStatus(201);
        }

        $this->assertTrue((bool) $venue->fresh()->is_flagged);
    }

    public function test_user_can_report_review(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $this->actingAsPlayer();

        $this->postJson("/api/v1/reviews/{$review->id}/report", [
            'reason' => 'offensive_language',
            'description' => 'يحتوي على ألفاظ مسيئة',
        ])->assertStatus(201);

        $this->assertEquals(1, $review->fresh()->reports_count);
    }

    public function test_cannot_report_own_review(): void
    {
        $user = $this->actingAsPlayer();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->postJson("/api/v1/reviews/{$review->id}/report", [
            'reason' => 'spam',
        ])->assertStatus(422);
    }

    public function test_review_auto_hidden_after_threshold(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $threshold = (int) config('bookings.moderation.auto_hide_review_threshold', 3);

        for ($i = 0; $i < $threshold; $i++) {
            $u = User::factory()->create();
            $u->assignRole('player');
            $this->actingAs($u, 'sanctum');
            $this->postJson("/api/v1/reviews/{$review->id}/report", [
                'reason' => 'spam',
            ])->assertStatus(201);
        }

        $this->assertTrue((bool) $review->fresh()->is_hidden);
    }
}
