<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\App\AppMetadataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppMetadataController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AppMetadataService $service) {}

    public function version(Request $request): JsonResponse
    {
        $platform = strtolower((string) $request->header('X-App-Platform', 'android'));
        if (! in_array($platform, ['ios', 'android'], true)) {
            return $this->validationError(['platform' => ['Invalid platform header']]);
        }

        $version = (string) $request->header('X-App-Version', '0.0.0');
        $build = (int) $request->header('X-App-Build', 0);

        return $this->success($this->service->checkVersion($platform, $version, $build));
    }

    public function featureFlags(Request $request): JsonResponse
    {
        return $this->success($this->service->getFeatureFlags($request->user()));
    }

    public function maintenance(): JsonResponse
    {
        return $this->success($this->service->getMaintenanceStatus());
    }

    public function config(): JsonResponse
    {
        return $this->success($this->service->getPublicConfig());
    }

    public function health(): JsonResponse
    {
        return $this->success($this->service->healthCheck());
    }
}
