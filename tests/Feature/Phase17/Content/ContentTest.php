<?php

namespace Tests\Feature\Phase17\Content;

use App\Models\Content\Banner;
use App\Models\Content\BlogArticle;
use App\Models\Content\Tip;
use App\Models\Content\Video;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_banners_filter_by_position_and_active_window(): void
    {
        Banner::create([
            'title' => 'Top', 'title_ar' => 'أعلى',
            'image_url' => 'a.jpg', 'position' => 'home_top', 'is_active' => true,
        ]);
        Banner::create([
            'title' => 'Mid', 'title_ar' => 'وسط',
            'image_url' => 'b.jpg', 'position' => 'home_middle', 'is_active' => true,
        ]);
        Banner::create([
            'title' => 'Expired', 'title_ar' => 'منتهي',
            'image_url' => 'c.jpg', 'position' => 'home_top', 'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $this->getJson('/api/v1/content/banners?position=home_top')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title_ar', 'أعلى');
    }

    public function test_invalid_banner_position_returns_422(): void
    {
        $this->getJson('/api/v1/content/banners?position=nope')
            ->assertStatus(422);
    }

    public function test_blog_returns_only_published(): void
    {
        BlogArticle::create([
            'slug' => 'a', 'title' => 'A', 'title_ar' => 'أ',
            'content' => '...', 'content_ar' => '...',
            'is_published' => true, 'published_at' => now()->subHour(),
        ]);
        BlogArticle::create([
            'slug' => 'b', 'title' => 'B', 'title_ar' => 'ب',
            'content' => '...', 'content_ar' => '...',
            'is_published' => false,
        ]);

        $this->getJson('/api/v1/content/blog')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.slug', 'a');
    }

    public function test_tips_filter_by_category(): void
    {
        Tip::create(['title_ar' => 'إحماء', 'content_ar' => '...', 'category' => 'training', 'is_active' => true]);
        Tip::create(['title_ar' => 'ماء', 'content_ar' => '...', 'category' => 'nutrition', 'is_active' => true]);
        Tip::create(['title_ar' => 'مخفي', 'content_ar' => '...', 'category' => 'training', 'is_active' => false]);

        $this->getJson('/api/v1/content/tips?category=training')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_videos_returns_paginated(): void
    {
        Video::create(['title_ar' => 'فيديو 1', 'youtube_id' => 'abc', 'category' => 'tutorial', 'is_active' => true]);
        Video::create(['title_ar' => 'فيديو 2', 'youtube_id' => 'def', 'category' => 'tutorial', 'is_active' => true]);

        $this->getJson('/api/v1/content/videos')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    }
}
