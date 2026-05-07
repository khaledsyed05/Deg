<?php

namespace Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Tests\TestCase;

class PaginatedEnvelopeMacroTest extends TestCase
{
    public function test_macro_emits_envelope_with_meta(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [['id' => 1], ['id' => 2], ['id' => 3]],
            total: 30,
            perPage: 3,
            currentPage: 4,
        );

        $response = Response::paginatedEnvelope($paginator, null, 'OK');
        $body = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame('OK', $body['message']);
        $this->assertSame([['id' => 1], ['id' => 2], ['id' => 3]], $body['data']);
        $this->assertNull($body['errors']);
        $this->assertSame([
            'current_page' => 4,
            'per_page' => 3,
            'total' => 30,
            'last_page' => 10,
        ], $body['meta']);
    }
}
