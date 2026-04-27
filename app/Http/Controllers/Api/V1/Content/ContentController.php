<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ApiResponse;

    public function __construct(private ContentService $contentService) {}

    public function banners(Request $request): JsonResponse
    {
        $position = $request->string('position', 'home_top')->value() ?: 'home_top';

        $allowed = config('content.banner_positions', [
            'home_top', 'home_middle', 'home_bottom', 'venue_list', 'event_list', 'profile',
        ]);

        if (! in_array($position, $allowed, true)) {
            return $this->validationError(['position' => 'موقع غير صحيح']);
        }

        return $this->success($this->contentService->getBanners($position));
    }

    public function featured(Request $request): JsonResponse
    {
        $section = $request->string('section', 'home_featured')->value() ?: 'home_featured';

        return $this->success($this->contentService->getFeaturedContent($section));
    }

    public function blog(Request $request): JsonResponse
    {
        $category = $request->string('category')->value() ?: null;
        $featured = $request->boolean('featured', false);
        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success(
            $this->contentService->getBlogArticles($category, $featured, $perPage)
        );
    }

    public function tips(Request $request): JsonResponse
    {
        $category = $request->string('category')->value() ?: null;
        $limit = $request->integer('limit', 0) ?: null;

        return $this->success($this->contentService->getTips($category, $limit));
    }

    public function videos(Request $request): JsonResponse
    {
        $category = $request->string('category')->value() ?: null;
        $perPage = min(50, max(1, $request->integer('per_page', 15)));

        return $this->success($this->contentService->getVideos($category, $perPage));
    }
}
