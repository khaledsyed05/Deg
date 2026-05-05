<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mobile Integration — Endpoint Verification (Sprint 0 skeleton)
|--------------------------------------------------------------------------
|
| Standalone PHP script (no Laravel boot) that parses a Postman collection
| and lists every (method, path) it contains. Sprint 0 stops here — Sprint 1
| extends this script to actually fire HTTP requests against the running
| application and assert that each endpoint returns the documented envelope.
|
| Usage:
|     php scripts/verify_endpoints.php <collection.json> <base_url>
|
| Example:
|     php scripts/verify_endpoints.php docs/postman/inferred/collection.json \
|         http://localhost:8000
|
| Exit codes:
|     0 — collection parsed, endpoint list printed
|     1 — invalid arguments or collection file unreadable / malformed
*/

if ($argc < 3) {
    fwrite(STDERR, "usage: php scripts/verify_endpoints.php <collection.json> <base_url>\n");
    exit(1);
}

$collectionPath = $argv[1];
$baseUrl = rtrim($argv[2], '/');

if (! is_file($collectionPath) || ! is_readable($collectionPath)) {
    fwrite(STDERR, "error: cannot read collection at: {$collectionPath}\n");
    exit(1);
}

$raw = file_get_contents($collectionPath);
if ($raw === false) {
    fwrite(STDERR, "error: file_get_contents failed for {$collectionPath}\n");
    exit(1);
}

$collection = json_decode($raw, true);
if (! is_array($collection)) {
    fwrite(STDERR, 'error: invalid JSON — '.json_last_error_msg()."\n");
    exit(1);
}

/**
 * Recursively walk a Postman collection v2.x `item` tree and yield every
 * leaf request.
 *
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
        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $path = extract_path($request['url'] ?? null);
        $name = (string) ($node['name'] ?? '(unnamed)');

        yield ['method' => $method, 'path' => $path, 'name' => $name];
    }
}

/**
 * Resolve the path portion of a Postman v2 url field. The field may be a
 * string or an object with `raw` / `path` keys.
 *
 * @param  mixed  $url
 */
function extract_path($url): string
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

$items = $collection['item'] ?? [];
if (! is_array($items)) {
    fwrite(STDERR, "error: collection has no top-level 'item' array\n");
    exit(1);
}

$endpoints = iterator_to_array(walk_items($items), false);

printf("Collection: %s\n", $collection['info']['name'] ?? '(unnamed)');
printf("Base URL  : %s\n", $baseUrl);
printf("Endpoints : %d\n\n", count($endpoints));

foreach ($endpoints as $endpoint) {
    printf("  %-6s  %s    [%s]\n", $endpoint['method'], $endpoint['path'], $endpoint['name']);
}

printf("\nSprint 0: enumeration complete. Sprint 1 will execute the requests.\n");

exit(0);
