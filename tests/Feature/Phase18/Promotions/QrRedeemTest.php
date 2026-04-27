<?php

namespace Tests\Feature\Phase18\Promotions;

use App\Models\Promotion;
use App\Models\QrPromotionRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QrRedeemTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeQrPromotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'code' => 'QR'.uniqid(),
            'slug' => 'qr-'.uniqid(),
            'qr_code' => 'DAQ-PROMO-'.uniqid(),
            'name' => ['ar' => 'عرض', 'en' => 'Promo'],
            'type' => 'percentage',
            'value' => 20,
            'status' => 'active',
            'is_qr_promotion' => true,
            'qr_redemption_limit' => 5,
            'qr_redemption_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addMonth(),
            'applies_to' => 'all',
        ], $overrides));
    }

    public function test_user_can_redeem_valid_qr_code(): void
    {
        $user = User::factory()->create();
        $promo = $this->makeQrPromotion();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/promotions/qr-redeem', ['qr_code' => $promo->qr_code])
            ->assertOk()
            ->assertJsonPath('data.promotion.id', $promo->id);

        $this->assertDatabaseHas('qr_promotion_redemptions', [
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_qr_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/promotions/qr-redeem', ['qr_code' => 'BAD-CODE'])
            ->assertStatus(404);
    }

    public function test_expired_qr_returns_410(): void
    {
        $user = User::factory()->create();
        $promo = $this->makeQrPromotion(['valid_to' => now()->subDay()]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/promotions/qr-redeem', ['qr_code' => $promo->qr_code])
            ->assertStatus(410);
    }

    public function test_double_redemption_blocked(): void
    {
        $user = User::factory()->create();
        $promo = $this->makeQrPromotion();
        QrPromotionRedemption::create([
            'promotion_id' => $promo->id,
            'user_id' => $user->id,
            'redeemed_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/promotions/qr-redeem', ['qr_code' => $promo->qr_code])
            ->assertStatus(422);
    }

    public function test_exhausted_qr_returns_410(): void
    {
        $user = User::factory()->create();
        $promo = $this->makeQrPromotion(['qr_redemption_limit' => 1, 'qr_redemption_count' => 1]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/promotions/qr-redeem', ['qr_code' => $promo->qr_code])
            ->assertStatus(410);
    }
}
