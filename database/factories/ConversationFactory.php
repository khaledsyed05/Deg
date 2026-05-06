<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'dm',
            'reference_id' => null,
        ];
    }

    public function dm(?User $u1 = null, ?User $u2 = null): static
    {
        return $this->state(fn () => ['type' => 'dm', 'reference_id' => null])
            ->afterCreating(function (Conversation $c) use ($u1, $u2): void {
                $u1 = $u1 ?? User::factory()->create();
                $u2 = $u2 ?? User::factory()->create();
                ConversationParticipant::create([
                    'conversation_id' => $c->id,
                    'user_id' => $u1->id,
                    'joined_at' => now(),
                ]);
                ConversationParticipant::create([
                    'conversation_id' => $c->id,
                    'user_id' => $u2->id,
                    'joined_at' => now(),
                ]);
            });
    }

    public function teamChannel(int $teamId): static
    {
        return $this->state(fn () => ['type' => 'team', 'reference_id' => $teamId]);
    }

    public function groupChannel(int $bookingId): static
    {
        return $this->state(fn () => ['type' => 'group', 'reference_id' => $bookingId]);
    }

    public function withParticipant(User $user): static
    {
        return $this->afterCreating(function (Conversation $c) use ($user): void {
            ConversationParticipant::firstOrCreate(
                ['conversation_id' => $c->id, 'user_id' => $user->id],
                ['joined_at' => now()],
            );
        });
    }
}
