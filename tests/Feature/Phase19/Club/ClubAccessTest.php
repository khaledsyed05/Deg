<?php

namespace Tests\Feature\Phase19\Club;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClubAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['club_manager', 'club_admin', 'club_staff'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }
    }

    private function makeClub(): Club
    {
        $cityId = DB::table('cities')->insertGetId([
            'state_id' => DB::table('states')->insertGetId([
                'country_id' => DB::table('countries')->insertGetId([
                    'name' => json_encode(['ar' => 'سوريا', 'en' => 'Syria']),
                    'iso2' => 'S'.strtoupper(substr(uniqid(), -1)),
                    'iso3' => 'S'.strtoupper(substr(uniqid(), -2)),
                    'phone_code' => '+963',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                'name' => json_encode(['ar' => 'دمشق', 'en' => 'Damascus']),
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'name_ar' => 'دمشق',
            'name' => json_encode(['ar' => 'دمشق', 'en' => 'Damascus']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Club::create([
            'city_id' => $cityId,
            'name' => ['ar' => 'نادي', 'en' => 'Club'],
            'slug' => 'club-'.uniqid(),
            'latitude' => 33.5,
            'longitude' => 36.3,
            'status' => 'active',
        ]);
    }

    public function test_user_without_club_role_blocked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/club/v1/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_club_manager_without_assigned_club_blocked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('club_manager');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/club/v1/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_club_manager_with_assigned_club_can_view_dashboard(): void
    {
        $club = $this->makeClub();
        $user = User::factory()->create(['managed_club_id' => $club->id]);
        $user->assignRole('club_manager');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/club/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure(['data' => ['total_venues', 'today_bookings', 'avg_rating']]);
    }

    public function test_club_user_can_post_update(): void
    {
        $club = $this->makeClub();
        $user = User::factory()->create(['managed_club_id' => $club->id]);
        $user->assignRole('club_manager');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/club/v1/updates', [
                'title' => 'New offer',
                'title_ar' => 'عرض جديد',
                'type' => 'announcement',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('club_updates', ['club_id' => $club->id, 'title_ar' => 'عرض جديد']);
    }
}
