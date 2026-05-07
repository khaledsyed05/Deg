<?php

namespace App\Services\Chat;

use App\Models\Booking;
use App\Models\Team;
use App\Models\User;
use Pusher\Pusher;

/**
 * The security boundary for Pusher private-channel subscriptions.
 *
 * Each private channel name is parsed against one of the three
 * documented spec patterns and authorized against the user's actual
 * membership before any Pusher signature is generated. A failure
 * here is the difference between "user can't subscribe" and
 * "user reads someone else's chat" — every test in
 * tests/Feature/Chat/PusherAuthTest.php exists to keep this honest.
 *
 * Channel naming convention (BACKEND_REQUIREMENTS.md L4687-4689):
 *   private-dm-{u1}-{u2}      where u1 < u2 (sorted ascending)
 *   private-team-{team_id}
 *   private-group-{booking_id}
 *
 * Anything else is rejected.
 */
class ChannelAuthorizer
{
    public function __construct(private Pusher $pusher) {}

    /**
     * Returns the Pusher signed-auth array on success, or `null` on
     * unauthorized. The controller maps `null` to a 403 envelope.
     *
     * @return array{auth: string}|null
     */
    public function authorize(User $user, string $channelName, string $socketId): ?array
    {
        if (! $this->isAuthorized($user, $channelName)) {
            return null;
        }

        return $this->signature($channelName, $socketId);
    }

    public function isAuthorized(User $user, string $channelName): bool
    {
        if (str_starts_with($channelName, 'private-dm-')) {
            return $this->isAuthorizedForDm($user, $channelName);
        }

        if (str_starts_with($channelName, 'private-team-')) {
            return $this->isAuthorizedForTeam($user, $channelName);
        }

        if (str_starts_with($channelName, 'private-group-')) {
            return $this->isAuthorizedForGroup($user, $channelName);
        }

        // Unknown private-* pattern, presence-* (out of scope this
        // sprint), or anything else: deny.
        return false;
    }

    private function isAuthorizedForDm(User $user, string $channelName): bool
    {
        // Format: private-dm-{u1}-{u2} where u1 < u2.
        $remainder = substr($channelName, strlen('private-dm-'));

        if (! preg_match('/^(\d+)-(\d+)$/', $remainder, $m)) {
            return false;
        }

        $u1 = (int) $m[1];
        $u2 = (int) $m[2];

        // Reject malformed (zero or non-ascending) channels.
        if ($u1 <= 0 || $u2 <= 0 || $u1 >= $u2) {
            return false;
        }

        return $user->id === $u1 || $user->id === $u2;
    }

    private function isAuthorizedForTeam(User $user, string $channelName): bool
    {
        $remainder = substr($channelName, strlen('private-team-'));

        if (! preg_match('/^\d+$/', $remainder)) {
            return false;
        }

        $teamId = (int) $remainder;

        if ($teamId <= 0) {
            return false;
        }

        $team = Team::find($teamId);

        if ($team === null) {
            return false;
        }

        return $team->hasMember($user->id);
    }

    private function isAuthorizedForGroup(User $user, string $channelName): bool
    {
        $remainder = substr($channelName, strlen('private-group-'));

        if (! preg_match('/^\d+$/', $remainder)) {
            return false;
        }

        $bookingId = (int) $remainder;

        if ($bookingId <= 0) {
            return false;
        }

        $booking = Booking::find($bookingId);

        if ($booking === null) {
            return false;
        }

        if ((int) $booking->user_id === $user->id) {
            return true;
        }

        return $booking->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * @return array{auth: string}
     */
    private function signature(string $channelName, string $socketId): array
    {
        $signed = $this->pusher->socketAuth($channelName, $socketId);

        // socketAuth() returns a JSON-encoded `{"auth":"..."}` string.
        $decoded = json_decode($signed, true);

        if (! is_array($decoded) || ! isset($decoded['auth']) || ! is_string($decoded['auth'])) {
            return ['auth' => (string) $signed];
        }

        return $decoded;
    }
}
