<?php

namespace Tests\Feature\Phase18\Reviews;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadPhotosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_upload_photos_to_their_review(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reviews/{$review->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->image('a.jpg', 800, 600),
                    UploadedFile::fake()->image('b.jpg', 800, 600),
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.total_photos', 2);

        $this->assertCount(2, $review->fresh()->photos);
    }

    public function test_max_5_photos_total(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'photos' => ['a', 'b', 'c', 'd'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reviews/{$review->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->image('e.jpg'),
                    UploadedFile::fake()->image('f.jpg'),
                ],
            ])
            ->assertStatus(422);
    }

    public function test_user_cannot_upload_to_someone_elses_review(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reviews/{$review->id}/photos", [
                'photos' => [UploadedFile::fake()->image('x.jpg')],
            ])
            ->assertStatus(404);
    }

    public function test_invalid_file_type_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reviews/{$review->id}/photos", [
                'photos' => [UploadedFile::fake()->create('doc.pdf', 100)],
            ])
            ->assertStatus(422);
    }
}
