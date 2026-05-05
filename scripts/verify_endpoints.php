<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mobile Integration — Endpoint Verification (Sprint 1)
|--------------------------------------------------------------------------
|
| Standalone PHP script (no Laravel boot) that:
|   1. Parses BACKEND_REQUIREMENTS.md and extracts every documented
|      endpoint (### `METHOD /path` heading + the surrounding metadata
|      and JSON Response example).
|   2. Hits each endpoint against a running base URL via curl, with an
|      optional bearer token for auth-required endpoints.
|   3. Compares the live response shape against the documented JSON
|      example and emits a per-endpoint ✅/⚠️/🔴 line plus a summary.
|
| The Postman-collection mode from Sprint 0 is preserved as a back-compat
| code path: if the first argument is a `.json` file the script falls
| back to enumerating items in the collection without firing requests.
|
| Usage:
|     php scripts/verify_endpoints.php <spec.md|collection.json> <base_url> [--token=...]
|
| Example:
|     php scripts/verify_endpoints.php BACKEND_REQUIREMENTS.md http://localhost:8000
|     php scripts/verify_endpoints.php BACKEND_REQUIREMENTS.md \
|         http://localhost:8000 --token=1|abcd...
|
| Exit codes:
|     0 — all P0 endpoints matched
|     1 — argument error / spec unreadable / one or more P0 mismatches
|
| Sprint 1 note: PHPUnit (tests/Feature/MobileEnvelope/) is the canonical
| verification path; this script is a fast sanity layer that runs against
| a live server.
*/

if ($argc < 3) {
    fwrite(STDERR, "usage: php scripts/verify_endpoints.php <spec.md|collection.json> <base_url> [--token=...]\n");
    exit(1);
}

$specPath = $argv[1];
$baseUrl = rtrim($argv[2], '/');
$token = null;
for ($i = 3; $i < $argc; $i++) {
    if (str_starts_with($argv[$i], '--token=')) {
        $token = substr($argv[$i], strlen('--token='));
    }
}

if (! is_file($specPath) || ! is_readable($specPath)) {
    fwrite(STDERR, "error: cannot read spec at: {$specPath}\n");
    exit(1);
}

if (str_ends_with(strtolower($specPath), '.json')) {
    exit(run_postman_mode($specPath, $baseUrl));
}

if (! str_ends_with(strtolower($specPath), '.md')) {
    fwrite(STDERR, "error: spec must be a .md (BACKEND_REQUIREMENTS.md) or .json (Postman) file\n");
    exit(1);
}

exit(run_spec_mode($specPath, $baseUrl, $token));

// =========================================================================
// Spec mode (BACKEND_REQUIREMENTS.md)
// =========================================================================

/**
 * Run end-to-end verification against BACKEND_REQUIREMENTS.md.
 */
function run_spec_mode(string $path, string $baseUrl, ?string $token): int
{
    $endpoints = parse_backend_requirements($path);

    if ($endpoints === []) {
        fwrite(STDERR, "error: no endpoints parsed from {$path}\n");

        return 1;
    }

    printf("Spec: %s\n", $path);
    printf("Base URL: %s\n", $baseUrl);
    printf("Endpoints parsed: %d\n", count($endpoints));
    printf("Bearer token: %s\n\n", $token === null ? '(none)' : '(set)');

    $counts = ['match' => 0, 'minor' => 0, 'major' => 0, 'skip' => 0];
    $p0_failures = 0;

    foreach ($endpoints as $endpoint) {
        $result = run_endpoint($endpoint, $baseUrl, $token);
        $verdict = grade($endpoint, $result);

        $counts[$verdict['bucket']]++;
        if ($endpoint['priority'] === 'P0' && $verdict['bucket'] !== 'match') {
            $p0_failures++;
        }

        printf(
            "%s  %-6s %-50s — %s\n",
            $verdict['icon'],
            $endpoint['method'],
            $endpoint['path'],
            $verdict['summary'],
        );
    }

    printf(
        "\nSummary: %d ✅ match, %d ⚠️ minor, %d 🔴 major, %d ⏸️ skipped (total %d)\n",
        $counts['match'], $counts['minor'], $counts['major'], $counts['skip'],
        array_sum($counts),
    );

    return $p0_failures > 0 ? 1 : 0;
}

/**
 * Parse BACKEND_REQUIREMENTS.md and return the list of endpoints documented
 * before the gap section.
 *
 * @return list<array{
 *     method: string,
 *     path: string,
 *     auth: string,
 *     priority: string,
 *     response_example: mixed,
 * }>
 */
function parse_backend_requirements(string $path): array
{
    $raw = (string) file_get_contents($path);
    $lines = explode("\n", $raw);

    $gapStart = count($lines);
    foreach ($lines as $i => $line) {
        if ($i === 0) {
            continue;
        }
        if (preg_match('/^# .* Gap Report/u', $line)) {
            $gapStart = $i;
            break;
        }
    }

    $endpoints = [];
    $currentP0 = 'P3';
    foreach ($lines as $i => $line) {
        if ($i >= $gapStart) {
            break;
        }
        if (preg_match('/^# .* P0 /u', $line)) {
            $currentP0 = 'P0';
        } elseif (preg_match('/^# .* P1 /u', $line)) {
            $currentP0 = 'P1';
        } elseif (preg_match('/^# .* P2 /u', $line)) {
            $currentP0 = 'P2';
        } elseif (preg_match('/^# .* P3 /u', $line)) {
            $currentP0 = 'P3';
        }

        if (! preg_match('/^### `(GET|POST|PUT|PATCH|DELETE) (\S+)`/', $line, $match)) {
            continue;
        }

        $method = $match[1];
        $endpointPath = $match[2];
        $block = collect_block($lines, $i, $gapStart);

        $endpoints[] = [
            'method' => $method,
            'path' => $endpointPath,
            'auth' => extract_auth($block),
            'priority' => extract_priority($block) ?? $currentP0,
            'response_example' => extract_response_json($block),
        ];
    }

    return $endpoints;
}

/**
 * Collect the lines between this endpoint heading and the next.
 *
 * @param  list<string>  $lines
 */
function collect_block(array $lines, int $startIndex, int $hardStop): string
{
    $end = $hardStop;
    for ($j = $startIndex + 1; $j < $hardStop; $j++) {
        if (preg_match('/^### `(GET|POST|PUT|PATCH|DELETE) /', $lines[$j])) {
            $end = $j;
            break;
        }
    }

    return implode("\n", array_slice($lines, $startIndex, $end - $startIndex));
}

function extract_auth(string $block): string
{
    if (preg_match('/\*\*Auth:\*\*\s*([^\n]+)/', $block, $m)) {
        $value = strtolower(trim($m[1]));
        if (str_starts_with($value, 'public')) {
            return 'public';
        }

        return 'bearer';
    }

    return 'bearer';
}

function extract_priority(string $block): ?string
{
    if (preg_match('/\*\*Priority:\*\*\s*(P[0-3])/', $block, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Pull the first ```json fenced block following a `**Response:**` label.
 */
function extract_response_json(string $block): mixed
{
    if (! preg_match('/\*\*Response:\*\*\s*\n```json\s*\n(.*?)\n```/sm', $block, $m)) {
        return null;
    }

    $decoded = json_decode($m[1], true);

    return is_array($decoded) ? $decoded : null;
}

/**
 * Replace path placeholders with conservative test values so curl gets a
 * URL it can resolve. Used for live mode only — PHPUnit tests construct
 * URLs with real factory values.
 */
function fill_placeholders(string $path): string
{
    return preg_replace_callback('/\{([^}]+)\}/', function (array $m): string {
        $name = strtolower($m[1]);
        if (str_contains($name, 'slug')) {
            return 'sample-slug';
        }
        if (str_contains($name, 'code') || str_contains($name, 'uuid')) {
            return 'sample-code';
        }

        return '1';
    }, $path);
}

/**
 * @param  array{method: string, path: string, auth: string, priority: string, response_example: mixed}  $spec
 * @return array{status: int, body: mixed, error: ?string}
 */
function run_endpoint(array $spec, string $baseUrl, ?string $token): array
{
    $url = $baseUrl.'/api/v1'.fill_placeholders($spec['path']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $spec['method'],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER => array_filter([
            'Accept: application/json',
            'Content-Type: application/json',
            $spec['auth'] === 'bearer' && $token !== null ? "Authorization: Bearer {$token}" : null,
        ]),
    ]);
    if (in_array($spec['method'], ['POST', 'PUT', 'PATCH'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = $errno !== 0 ? curl_error($ch) : null;
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return ['status' => 0, 'body' => null, 'error' => $error];
    }

    $decoded = json_decode((string) $body, true);

    return [
        'status' => $status,
        'body' => $decoded,
        'error' => $error,
    ];
}

/**
 * @param  array{method: string, path: string, auth: string, priority: string, response_example: mixed}  $spec
 * @param  array{status: int, body: mixed, error: ?string}  $result
 * @return array{bucket: string, icon: string, summary: string}
 */
function grade(array $spec, array $result): array
{
    if ($result['error'] !== null || $result['status'] === 0) {
        return [
            'bucket' => 'skip',
            'icon' => '⏸️',
            'summary' => 'curl error: '.($result['error'] ?? 'no response — server unreachable?'),
        ];
    }

    if ($result['status'] >= 500) {
        return [
            'bucket' => 'major',
            'icon' => '🔴',
            'summary' => "HTTP {$result['status']} (server error)",
        ];
    }

    $body = $result['body'];
    if (! is_array($body)) {
        return [
            'bucket' => 'major',
            'icon' => '🔴',
            'summary' => "HTTP {$result['status']} non-JSON body",
        ];
    }

    if (! array_key_exists('success', $body)) {
        return [
            'bucket' => 'major',
            'icon' => '🔴',
            'summary' => "HTTP {$result['status']} envelope missing 'success'",
        ];
    }

    if ($spec['response_example'] === null) {
        return [
            'bucket' => 'match',
            'icon' => '✅',
            'summary' => "HTTP {$result['status']} envelope OK (no spec example to compare)",
        ];
    }

    $diff = compare_shapes($spec['response_example'], $body);
    if ($diff['matches']) {
        return [
            'bucket' => 'match',
            'icon' => '✅',
            'summary' => "HTTP {$result['status']} matches spec",
        ];
    }

    $missing = count($diff['missing_keys']);
    $mismatches = count($diff['type_mismatches']);
    $bucket = $missing > 0 ? 'major' : 'minor';

    return [
        'bucket' => $bucket,
        'icon' => $bucket === 'major' ? '🔴' : '⚠️',
        'summary' => sprintf(
            'HTTP %d %d missing key(s), %d type mismatch(es)',
            $result['status'], $missing, $mismatches,
        ),
    ];
}

/**
 * Walk an expected (spec) JSON example and check the actual response has
 * matching keys with compatible types. Null in the expected example is a
 * type wildcard.
 *
 * @return array{
 *     matches: bool,
 *     missing_keys: list<string>,
 *     type_mismatches: array<string, array{0: string, 1: string}>,
 *     extra_keys: list<string>,
 * }
 */
function compare_shapes(mixed $expected, mixed $actual, string $prefix = ''): array
{
    $missing = [];
    $type_mismatches = [];
    $extra = [];

    if (is_array($expected) && array_is_list($expected)) {
        if (! is_array($actual) || ! array_is_list($actual)) {
            $type_mismatches[$prefix ?: '$'] = ['list', describe_type($actual)];
        } elseif ($expected !== [] && $actual !== []) {
            $sub = compare_shapes($expected[0], $actual[0], $prefix.'[0]');
            $missing = array_merge($missing, $sub['missing_keys']);
            $type_mismatches += $sub['type_mismatches'];
            $extra = array_merge($extra, $sub['extra_keys']);
        }

        return [
            'matches' => $missing === [] && $type_mismatches === [],
            'missing_keys' => $missing,
            'type_mismatches' => $type_mismatches,
            'extra_keys' => $extra,
        ];
    }

    if (is_array($expected)) {
        if (! is_array($actual)) {
            $type_mismatches[$prefix ?: '$'] = ['object', describe_type($actual)];

            return [
                'matches' => false,
                'missing_keys' => $missing,
                'type_mismatches' => $type_mismatches,
                'extra_keys' => $extra,
            ];
        }

        foreach ($expected as $key => $expValue) {
            $childPrefix = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (! array_key_exists($key, $actual)) {
                $missing[] = $childPrefix;

                continue;
            }
            $sub = compare_shapes($expValue, $actual[$key], $childPrefix);
            $missing = array_merge($missing, $sub['missing_keys']);
            $type_mismatches += $sub['type_mismatches'];
            $extra = array_merge($extra, $sub['extra_keys']);
        }
        foreach (array_keys($actual) as $key) {
            if (! array_key_exists($key, $expected)) {
                $extra[] = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            }
        }

        return [
            'matches' => $missing === [] && $type_mismatches === [],
            'missing_keys' => $missing,
            'type_mismatches' => $type_mismatches,
            'extra_keys' => $extra,
        ];
    }

    if ($expected === null) {
        return [
            'matches' => true,
            'missing_keys' => $missing,
            'type_mismatches' => $type_mismatches,
            'extra_keys' => $extra,
        ];
    }

    $expType = describe_type($expected);
    $actType = describe_type($actual);
    if ($expType !== $actType && $actType !== 'null') {
        $type_mismatches[$prefix ?: '$'] = [$expType, $actType];

        return [
            'matches' => false,
            'missing_keys' => $missing,
            'type_mismatches' => $type_mismatches,
            'extra_keys' => $extra,
        ];
    }

    return [
        'matches' => true,
        'missing_keys' => $missing,
        'type_mismatches' => $type_mismatches,
        'extra_keys' => $extra,
    ];
}

function describe_type(mixed $value): string
{
    if (is_array($value)) {
        return array_is_list($value) ? 'list' : 'object';
    }
    if ($value === null) {
        return 'null';
    }
    if (is_int($value)) {
        return 'int';
    }
    if (is_float($value)) {
        return 'float';
    }
    if (is_bool($value)) {
        return 'bool';
    }
    if (is_string($value)) {
        return 'string';
    }

    return get_debug_type($value);
}

// =========================================================================
// Postman mode (Sprint 0 back-compat)
// =========================================================================

function run_postman_mode(string $path, string $baseUrl): int
{
    $raw = (string) file_get_contents($path);
    $collection = json_decode($raw, true);
    if (! is_array($collection)) {
        fwrite(STDERR, 'error: invalid JSON — '.json_last_error_msg()."\n");

        return 1;
    }

    $items = $collection['item'] ?? [];
    if (! is_array($items)) {
        fwrite(STDERR, "error: collection has no top-level 'item' array\n");

        return 1;
    }

    $endpoints = iterator_to_array(walk_items($items), false);
    printf("Collection: %s\n", $collection['info']['name'] ?? '(unnamed)');
    printf("Base URL  : %s\n", $baseUrl);
    printf("Endpoints : %d\n\n", count($endpoints));

    foreach ($endpoints as $endpoint) {
        printf("  %-6s  %s    [%s]\n", $endpoint['method'], $endpoint['path'], $endpoint['name']);
    }

    return 0;
}

/**
 * @param  array<int|string, mixed>  $items
 * @return iterable<array{method: string, path: string, name: string}>
 */
function walk_items(array $items): iterable
{
    foreach ($items as $node) {
        if (! is_array($node)) {
            continue;
        }
        if (isset($node['item']) && is_array($node['item'])) {
            yield from walk_items($node['item']);

            continue;
        }
        if (! isset($node['request']) || ! is_array($node['request'])) {
            continue;
        }
        $request = $node['request'];
        yield [
            'method' => strtoupper((string) ($request['method'] ?? 'GET')),
            'path' => extract_postman_path($request['url'] ?? null),
            'name' => (string) ($node['name'] ?? '(unnamed)'),
        ];
    }
}

function extract_postman_path(mixed $url): string
{
    if (is_string($url)) {
        return $url;
    }
    if (is_array($url)) {
        if (isset($url['raw']) && is_string($url['raw'])) {
            return $url['raw'];
        }
        if (isset($url['path']) && is_array($url['path'])) {
            return '/'.implode('/', array_map('strval', $url['path']));
        }
    }

    return '(unknown)';
}
