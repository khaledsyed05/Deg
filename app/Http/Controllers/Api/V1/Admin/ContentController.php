<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\Content\Banner;
use App\Models\Content\BlogArticle;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    use ApiResponse;

    public function createBanner(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:1000',
            'subtitle_ar' => 'sometimes|nullable|string|max:1000',
            'image_url' => 'required|string|url|max:500',
            'image_url_dark' => 'sometimes|nullable|string|url|max:500',
            'link_type' => 'sometimes|nullable|string|max:50',
            'link_value' => 'sometimes|nullable|string|max:500',
            'position' => 'required|in:home_top,home_middle,home_bottom,venue_list,event_list,profile',
            'display_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'starts_at' => 'sometimes|nullable|date',
            'ends_at' => 'sometimes|nullable|date|after:starts_at',
        ]);

        $banner = Banner::create($data);
        Cache::forget("banners_{$banner->position}");

        AuditLog::record('banner.created', $request->user()->id, $banner, [], $request->ip());

        return $this->success($banner, 'تم إنشاء البانر', 201);
    }

    public function updateBanner(int $id, Request $request): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'title_ar' => 'sometimes|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:1000',
            'subtitle_ar' => 'sometimes|nullable|string|max:1000',
            'image_url' => 'sometimes|string|url|max:500',
            'image_url_dark' => 'sometimes|nullable|string|url|max:500',
            'position' => 'sometimes|in:home_top,home_middle,home_bottom,venue_list,event_list,profile',
            'display_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'starts_at' => 'sometimes|nullable|date',
            'ends_at' => 'sometimes|nullable|date|after:starts_at',
        ]);

        $banner->update($data);
        Cache::forget("banners_{$banner->position}");

        AuditLog::record('banner.updated', $request->user()->id, $banner, $data, $request->ip());

        return $this->success($banner->fresh(), 'تم تحديث البانر');
    }

    public function createBlogArticle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'excerpt' => 'sometimes|nullable|string|max:500',
            'excerpt_ar' => 'sometimes|nullable|string|max:500',
            'content' => 'required|string',
            'content_ar' => 'required|string',
            'cover_image_url' => 'sometimes|nullable|string|url|max:500',
            'category' => 'sometimes|string|max:100',
            'tags' => 'sometimes|nullable|array',
            'author_name' => 'sometimes|nullable|string|max:255',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'published_at' => 'sometimes|nullable|date',
            'reading_time_minutes' => 'sometimes|integer|min:1|max:60',
        ]);

        $article = BlogArticle::create($data);

        AuditLog::record('blog_article.created', $request->user()->id, $article, [], $request->ip());

        return $this->success($article, 'تم إنشاء المقال', 201);
    }

    public function createPromotion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'club_id' => 'sometimes|nullable|integer|exists:clubs,id',
            'venue_id' => 'sometimes|nullable|integer|exists:venues,id',
            'code' => 'required|string|max:50|unique:promotions,code',
            'name' => 'required|array',
            'name.ar' => 'required|string',
            'name.en' => 'sometimes|string',
            'description' => 'sometimes|nullable|array',
            'type' => 'required|in:percentage,fixed_amount,free_hours',
            'value' => 'required|integer|min:1',
            'min_amount' => 'sometimes|nullable|integer|min:0',
            'max_discount' => 'sometimes|nullable|integer|min:0',
            'valid_from' => 'sometimes|nullable|date',
            'valid_to' => 'sometimes|nullable|date|after:valid_from',
            'max_uses' => 'sometimes|nullable|integer|min:1',
            'max_uses_per_user' => 'sometimes|nullable|integer|min:1',
            'status' => 'sometimes|in:draft,active,inactive',
            'is_qr_promotion' => 'sometimes|boolean',
            'qr_redemption_limit' => 'sometimes|integer|min:1',
        ]);

        $data['slug'] = Str::slug($data['code']).'-'.substr(uniqid(), -4);

        if (! empty($data['is_qr_promotion'])) {
            $data['qr_code'] = 'DAQ-PROMO-'.strtoupper(Str::random(10));
        }

        $promotion = Promotion::create($data);

        AuditLog::record('promotion.created', $request->user()->id, $promotion, [], $request->ip());

        return $this->success($promotion, 'تم إنشاء العرض', 201);
    }
}
