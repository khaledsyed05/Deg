<?php

namespace Tests\Unit;

use App\Http\Traits\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResponseTraitTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use ApiResponse {
                success as public;
                noContent as public;
                paginated as public;
                error as public;
                validationError as public;
                notFound as public;
                unauthorized as public;
                forbidden as public;
            }
        };
    }

    public function test_success_envelope_has_all_four_keys(): void
    {
        $response = $this->subject->success(['id' => 1]);
        $body = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertNull($body['message']);
        $this->assertSame(['id' => 1], $body['data']);
        $this->assertNull($body['errors']);
    }

    public function test_success_envelope_with_message_and_status(): void
    {
        $response = $this->subject->success(['x' => 'y'], 'تم الحفظ', 201);
        $body = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('تم الحفظ', $body['message']);
        $this->assertSame(['x' => 'y'], $body['data']);
    }

    public function test_no_content_emits_data_null(): void
    {
        $response = $this->subject->noContent('تم الحذف');
        $body = $response->getData(true);

        $this->assertTrue($body['success']);
        $this->assertSame('تم الحذف', $body['message']);
        $this->assertArrayHasKey('data', $body);
        $this->assertNull($body['data']);
        $this->assertNull($body['errors']);
    }

    public function test_paginated_envelope_has_meta_keys(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [['id' => 1], ['id' => 2]],
            total: 42,
            perPage: 10,
            currentPage: 2,
        );

        $response = $this->subject->paginated($paginator);
        $body = $response->getData(true);

        $this->assertTrue($body['success']);
        $this->assertNull($body['errors']);
        $this->assertSame([['id' => 1], ['id' => 2]], $body['data']);
        $this->assertSame([
            'current_page' => 2,
            'per_page' => 10,
            'total' => 42,
            'last_page' => 5,
        ], $body['meta']);
    }

    public function test_error_envelope_has_data_null_and_errors(): void
    {
        $response = $this->subject->error('Bad', ['field' => ['nope']], 422);
        $body = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('Bad', $body['message']);
        $this->assertArrayHasKey('data', $body);
        $this->assertNull($body['data']);
        $this->assertSame(['field' => ['nope']], $body['errors']);
    }

    public function test_validation_error_uses_status_422(): void
    {
        $response = $this->subject->validationError(['phone' => ['required']]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_not_found_uses_status_404(): void
    {
        $response = $this->subject->notFound();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_unauthorized_uses_status_401(): void
    {
        $this->assertSame(401, $this->subject->unauthorized()->getStatusCode());
    }

    public function test_forbidden_uses_status_403(): void
    {
        $this->assertSame(403, $this->subject->forbidden()->getStatusCode());
    }
}
