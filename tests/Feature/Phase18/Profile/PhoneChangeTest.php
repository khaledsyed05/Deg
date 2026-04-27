<?php

namespace Tests\Feature\Phase18\Profile;

use App\Models\PhoneChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PhoneChangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_initiate_returns_masked_phone_and_request_id(): void
    {
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/initiate', [
                'new_phone_number' => '+963944333444',
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['phone_change_request_id', 'new_phone_number_masked', 'otp_expires_in_seconds']]);

        $this->assertDatabaseHas('phone_change_requests', [
            'user_id' => $user->id,
            'new_phone' => '+963944333444',
            'status' => 'pending',
        ]);
    }

    public function test_initiate_rejects_same_phone(): void
    {
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/initiate', [
                'new_phone_number' => '+963944111222',
            ])
            ->assertStatus(422);
    }

    public function test_initiate_rejects_phone_in_use(): void
    {
        $user = User::factory()->create(['phone_number' => '+963944111222']);
        User::factory()->create(['phone_number' => '+963944333444']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/initiate', [
                'new_phone_number' => '+963944333444',
            ])
            ->assertStatus(422);
    }

    public function test_initiate_rate_limits_after_3_per_hour(): void
    {
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($user, 'sanctum')
                ->putJson('/api/v1/profile/phone-number/initiate', [
                    'new_phone_number' => "+963944333{$i}{$i}{$i}",
                ])->assertOk();
        }

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/initiate', [
                'new_phone_number' => '+963944999000',
            ])
            ->assertStatus(429);
    }

    public function test_verify_with_master_otp_in_local_env(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        $initiate = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/initiate', [
                'new_phone_number' => '+963944333444',
            ])->assertOk();

        $reqId = $initiate->json('data.phone_change_request_id');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/verify', [
                'phone_change_request_id' => $reqId,
                'otp' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone_number', '+963944333444');

        $this->assertSame('+963944333444', $user->fresh()->phone_number);
    }

    public function test_verify_with_wrong_otp_fails(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        $req = PhoneChangeRequest::create([
            'user_id' => $user->id,
            'current_phone' => $user->phone_number,
            'new_phone' => '+963944555666',
            'otp_hash' => bcrypt('123456'),
            'otp_expires_at' => now()->addMinutes(5),
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/verify', [
                'phone_change_request_id' => $req->id,
                'otp' => '999999',
            ])
            ->assertStatus(422);
    }

    public function test_expired_otp_returns_410(): void
    {
        $user = User::factory()->create(['phone_number' => '+963944111222']);

        $req = PhoneChangeRequest::create([
            'user_id' => $user->id,
            'current_phone' => $user->phone_number,
            'new_phone' => '+963944555666',
            'otp_hash' => bcrypt('123456'),
            'otp_expires_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/phone-number/verify', [
                'phone_change_request_id' => $req->id,
                'otp' => '123456',
            ])
            ->assertStatus(410);
    }
}
