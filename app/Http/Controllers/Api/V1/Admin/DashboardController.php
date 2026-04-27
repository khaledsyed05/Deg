<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminDashboardService $service) {}

    public function stats(): JsonResponse
    {
        return $this->success($this->service->getStats());
    }

    public function revenue(Request $request): JsonResponse
    {
        $period = $request->string('period', 'monthly')->value() ?: 'monthly';
        $from = $request->date('from');
        $to = $request->date('to');

        return $this->success($this->service->getRevenue($period, $from, $to));
    }

    public function bookingsTrend(Request $request): JsonResponse
    {
        $days = min(365, max(1, $request->integer('days', 30)));

        return $this->success($this->service->getBookingsTrend($days));
    }

    public function topVenues(Request $request): JsonResponse
    {
        $limit = min(50, max(1, $request->integer('limit', 10)));

        return $this->success($this->service->getTopVenues($limit));
    }

    public function userGrowth(Request $request): JsonResponse
    {
        $days = min(365, max(1, $request->integer('days', 30)));

        return $this->success($this->service->getUserGrowth($days));
    }
}
