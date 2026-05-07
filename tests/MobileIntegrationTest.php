<?php

namespace Tests;

use App\Models\Club;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;

/**
 * Base test class for the Mobile Integration plan.
 *
 * Provides assertions for the response envelope contract documented in
 * docs/api-handoff/mobile-integration-guide.md (the working substitute for
 * BACKEND_REQUIREMENTS.md until that file lands).
 *
 * The contract:
 *   Success envelope:   { "success": true,  "message": string|null, "data": <any>, "errors"?: null, "meta"?: <object|null> }
 *   Paginated envelope: success envelope PLUS meta.{current_page,per_page,total,last_page} (all integers)
 *   Error envelope:     { "success": false, "message": string,      "errors": object|array|null }
 *
 * Tests in Sprints 1+ that need envelope assertions should extend this class
 * instead of Tests\TestCase directly.
 */
abstract class MobileIntegrationTest extends TestCase
{
    /**
     * Allowed roles for actingAsRole(). Mirrors the seeded roles in
     * Database\Seeders\RolesAndPermissionsSeeder and the four roles named in
     * the Sprint 0 prompt.
     *
     * @var list<string>
     */
    protected const ALLOWED_ROLES = ['player', 'club_manager', 'club_staff', 'admin'];

    /**
     * Assert that the response body is a documented success envelope.
     *
     * Required keys:
     *   - success: bool
     *   - message: string|null
     *   - data:    any (key must be present)
     * Optional keys (must be present-or-null, never missing):
     *   - errors:  null|array
     *   - meta:    null|array
     */
    protected function assertEnvelope(TestResponse $response): void
    {
        $body = $this->decodeJson($response);

        Assert::assertArrayHasKey('success', $body, 'Envelope missing "success" key');
        Assert::assertIsBool($body['success'], 'Envelope "success" must be a boolean');

        Assert::assertArrayHasKey('message', $body, 'Envelope missing "message" key');
        Assert::assertTrue(
            is_string($body['message']) || is_null($body['message']),
            'Envelope "message" must be string or null',
        );

        Assert::assertArrayHasKey('data', $body, 'Envelope missing "data" key');

        foreach (['errors', 'meta'] as $optional) {
            if (array_key_exists($optional, $body)) {
                Assert::assertTrue(
                    is_null($body[$optional]) || is_array($body[$optional]),
                    sprintf('Envelope "%s" must be null or an array/object when present', $optional),
                );
            }
        }
    }

    /**
     * Assert that the response body is a paginated envelope.
     */
    protected function assertPaginatedEnvelope(TestResponse $response): void
    {
        $this->assertEnvelope($response);

        $body = $this->decodeJson($response);

        Assert::assertArrayHasKey('meta', $body, 'Paginated envelope missing "meta" key');
        Assert::assertIsArray($body['meta'], 'Paginated envelope "meta" must be an object');

        foreach (['current_page', 'per_page', 'total', 'last_page'] as $key) {
            Assert::assertArrayHasKey($key, $body['meta'], sprintf('Pagination meta missing "%s"', $key));
            Assert::assertIsInt($body['meta'][$key], sprintf('Pagination meta "%s" must be an integer', $key));
        }
    }

    /**
     * Assert that the response is an error envelope with the given status code.
     */
    protected function assertErrorEnvelope(TestResponse $response, int $statusCode): void
    {
        Assert::assertSame(
            $statusCode,
            $response->getStatusCode(),
            sprintf('Expected HTTP %d, got %d', $statusCode, $response->getStatusCode()),
        );

        $body = $this->decodeJson($response);

        Assert::assertArrayHasKey('success', $body, 'Error envelope missing "success" key');
        Assert::assertFalse($body['success'], 'Error envelope "success" must be false');

        Assert::assertArrayHasKey('message', $body, 'Error envelope missing "message" key');
        Assert::assertIsString($body['message'], 'Error envelope "message" must be a string');

        Assert::assertArrayHasKey('errors', $body, 'Error envelope missing "errors" key');
        Assert::assertTrue(
            is_null($body['errors']) || is_array($body['errors']),
            'Error envelope "errors" must be null or an array/object',
        );
    }

    /**
     * Authenticate as a user with the given role using Sanctum.
     *
     * The role must already be seeded (RolesAndPermissionsSeeder runs in the
     * parent TestCase setUp). For 'club_manager' a Club is also attached so
     * authorization checks that look at owner_id pass.
     */
    protected function actingAsRole(string $role): User
    {
        if (! in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown role "%s". Allowed: %s',
                $role,
                implode(', ', self::ALLOWED_ROLES),
            ));
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        if ($role === 'club_manager') {
            $club = Club::factory()->create(['status' => 'active', 'owner_id' => $user->id]);
            $club->managers()->attach($user->id);
        }

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(TestResponse $response): array
    {
        $decoded = json_decode($response->getContent() ?: 'null', true);

        Assert::assertIsArray(
            $decoded,
            sprintf('Response body is not a JSON object. Got: %s', mb_substr((string) $response->getContent(), 0, 200)),
        );

        return $decoded;
    }
}
