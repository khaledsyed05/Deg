<?php

namespace Tests\Feature\Phase17\Emergency;

use App\Models\Emergency\EmergencyContact;
use App\Models\Emergency\EmergencyLocationShare;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmergencyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_emergency_report_requires_auth(): void
    {
        $this->postJson('/api/v1/emergency/report', [
            'type' => 'medical', 'description' => 'something happened here.',
        ])->assertUnauthorized();
    }

    public function test_user_can_report_emergency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/emergency/report', [
                'type' => 'medical',
                'severity' => 'high',
                'description' => 'A player got injured during the match.',
                'location' => ['latitude' => 33.5, 'longitude' => 36.3],
            ])
            ->assertCreated()
            ->assertJsonPath('data.severity', 'high')
            ->assertJsonPath('data.estimated_response_minutes', 15);

        $this->assertDatabaseHas('emergency_reports', [
            'user_id' => $user->id,
            'type' => 'medical',
            'severity' => 'high',
            'status' => 'reported',
        ]);
    }

    public function test_emergency_report_validates_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/emergency/report', [
                'type' => 'invalid',
                'description' => 'something serious happened.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_emergency_contacts_grouped_by_type(): void
    {
        EmergencyContact::create(['name' => 'Police', 'name_ar' => 'الشرطة', 'phone' => '112', 'type' => 'police', 'is_active' => true]);
        EmergencyContact::create(['name' => 'Ambulance', 'name_ar' => 'الإسعاف', 'phone' => '110', 'type' => 'ambulance', 'is_active' => true]);

        $response = $this->getJson('/api/v1/emergency/contacts')->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_safety_guide_returns_sections(): void
    {
        $this->getJson('/api/v1/emergency/safety-guide')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'sections' => [['title_ar', 'icon', 'tips']],
                    'emergency_numbers',
                ],
            ]);
    }

    public function test_user_can_start_location_share(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/emergency/share-location', [
                'duration_minutes' => 60,
            ])
            ->assertCreated();

        $token = $response->json('data.share_token');
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token));

        $share = EmergencyLocationShare::where('share_token', $token)->first();
        $this->assertNotNull($share);
        $this->assertTrue($share->is_active);
        $this->assertSame($user->id, $share->user_id);
    }

    public function test_share_location_validates_duration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/emergency/share-location', [
                'duration_minutes' => 5,
            ])
            ->assertStatus(422);
    }
}
