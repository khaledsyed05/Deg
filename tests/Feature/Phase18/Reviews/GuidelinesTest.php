<?php

namespace Tests\Feature\Phase18\Reviews;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GuidelinesTest extends TestCase
{
    public function test_guidelines_returns_structure(): void
    {
        Cache::flush();

        $this->getJson('/api/v1/reviews/guidelines')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'version',
                    'last_updated',
                    'guidelines' => [['category', 'title_ar', 'items']],
                    'rating_explanation' => [['stars', 'label_ar', 'description_ar']],
                ],
            ]);
    }

    public function test_guidelines_no_auth_required(): void
    {
        $this->getJson('/api/v1/reviews/guidelines')->assertOk();
    }
}
