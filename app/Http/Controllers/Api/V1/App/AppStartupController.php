<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\App\AppStartupRequest;
use App\Http\Traits\ApiResponse;
use App\Services\App\AppStartupService;
use Illuminate\Http\JsonResponse;

class AppStartupController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AppStartupService $service) {}

    public function __invoke(AppStartupRequest $request): JsonResponse
    {
        $data = $this->service->resolve(
            $request->string('platform')->toString(),
            $request->string('app_version')->toString(),
        );

        return $this->success($data, 'App startup config');
    }
}
