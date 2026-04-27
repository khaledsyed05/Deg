<?php

namespace App\Services\Content;

use App\Models\Content\Banner;
use App\Models\Content\BlogArticle;
use App\Models\Content\FeaturedContent;
use App\Models\Content\Tip;
use App\Models\Content\Video;
use Illuminate\Support\Facades\Cache;

class ContentService
{
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = (int) config('content.cache_ttl', 1800);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBanners(string $position = 'home_top'): array
    {
        return Cache::remember("banners_{$position}", $this->cacheTtl, function () use ($position) {
            return Banner::active()
                ->forPosition($position)
                ->get()
                ->map(fn (Banner $b) => [
                    'id' => $b->id,
                    'title_ar' => $b->title_ar,
                    'subtitle_ar' => $b->subtitle_ar,
                    'image_url' => $b->image_url,
                    'image_url_dark' => $b->image_url_dark,
                    'link' => [
                        'type' => $b->link_type,
                        'value' => $b->link_value,
                    ],
                    'display_order' => (int) $b->display_order,
                ])->toArray();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedContent(string $section = 'home_featured'): array
    {
        return Cache::remember("featured_{$section}", $this->cacheTtl, function () use ($section) {
            $items = FeaturedContent::active()
                ->where('section', $section)
                ->orderBy('display_order')
                ->with('contentable')
                ->get();

            return $items->map(function (FeaturedContent $item) {
                $content = $item->contentable;
                if (! $content) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'section' => $item->section,
                    'title_ar' => $item->title_ar,
                    'subtitle_ar' => $item->subtitle_ar,
                    'type' => class_basename($item->contentable_type),
                    'item_id' => $item->contentable_id,
                    'data' => $this->formatContentable($content),
                ];
            })->filter()->values()->toArray();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getBlogArticles(?string $category = null, bool $featuredOnly = false, int $perPage = 15): array
    {
        $cacheKey = 'blog_'.md5(($category ?? 'all').'_'.(int) $featuredOnly.'_'.$perPage);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category, $featuredOnly, $perPage) {
            $query = BlogArticle::published();

            if ($category) {
                $query->where('category', $category);
            }

            if ($featuredOnly) {
                $query->featured();
            }

            $articles = $query->latest('published_at')->paginate($perPage);

            return [
                'data' => $articles->getCollection()->map(fn (BlogArticle $a) => [
                    'id' => $a->id,
                    'slug' => $a->slug,
                    'title_ar' => $a->title_ar,
                    'excerpt_ar' => $a->excerpt_ar,
                    'cover_image_url' => $a->cover_image_url,
                    'category' => $a->category,
                    'tags' => $a->tags ?? [],
                    'author' => [
                        'name' => $a->author_name,
                        'avatar_url' => $a->author_avatar_url,
                    ],
                    'reading_time_minutes' => (int) $a->reading_time_minutes,
                    'views_count' => (int) $a->views_count,
                    'is_featured' => (bool) $a->is_featured,
                    'published_at' => $a->published_at?->toIso8601String(),
                ])->toArray(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'total' => $articles->total(),
                ],
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTips(?string $category = null, ?int $limit = null): array
    {
        $cacheKey = 'tips_'.md5(($category ?? 'all').'_'.($limit ?? 'all'));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category, $limit) {
            $query = Tip::active();

            if ($category) {
                $query->where('category', $category);
            }

            if ($limit) {
                $query->limit($limit);
            }

            return $query->get()->map(fn (Tip $t) => [
                'id' => $t->id,
                'title_ar' => $t->title_ar,
                'content_ar' => $t->content_ar,
                'icon_name' => $t->icon_name,
                'category' => $t->category,
                'image_url' => $t->image_url,
                'is_featured' => (bool) $t->is_featured,
            ])->toArray();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideos(?string $category = null, int $perPage = 15): array
    {
        $cacheKey = 'videos_'.md5(($category ?? 'all').'_'.$perPage);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category, $perPage) {
            $query = Video::active();

            if ($category) {
                $query->where('category', $category);
            }

            $videos = $query->paginate($perPage);

            return [
                'data' => $videos->getCollection()->map(fn (Video $v) => [
                    'id' => $v->id,
                    'title_ar' => $v->title_ar,
                    'description_ar' => $v->description_ar,
                    'youtube_id' => $v->youtube_id,
                    'youtube_url' => $v->youtube_url,
                    'thumbnail_url' => $v->thumbnail_url,
                    'duration_seconds' => (int) $v->duration_seconds,
                    'category' => $v->category,
                    'tags' => $v->tags ?? [],
                    'is_featured' => (bool) $v->is_featured,
                ])->toArray(),
                'meta' => [
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'total' => $videos->total(),
                ],
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatContentable(object $content): array
    {
        $base = ['id' => $content->id ?? null];

        $known = ['slug', 'name_ar', 'title_ar', 'logo_url', 'cover_image_url', 'image_url', 'address_ar'];

        foreach ($known as $field) {
            if (isset($content->{$field})) {
                $base[$field] = $content->{$field};
            }
        }

        return $base;
    }
}
