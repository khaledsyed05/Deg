<?php

namespace App\Http\Controllers\Api\V1\Emergency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Emergency\ReportEmergencyRequest;
use App\Http\Requests\Api\V1\Emergency\ShareLocationRequest;
use App\Http\Traits\ApiResponse;
use App\Services\Emergency\EmergencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    use ApiResponse;

    public function __construct(private EmergencyService $emergencyService) {}

    public function report(ReportEmergencyRequest $request): JsonResponse
    {
        $report = $this->emergencyService->reportEmergency(
            $request->user(),
            $request->validated('type'),
            $request->validated('description'),
            $request->validated('location'),
            $request->validated('booking_id'),
            $request->validated('severity', 'medium'),
            $request->validated('contact_phone'),
            (array) $request->validated('attachments', []),
        );

        return $this->success([
            'report_id' => $report->id,
            'status' => $report->status,
            'severity' => $report->severity,
            'estimated_response_minutes' => $this->getEstimatedResponseTime($report->severity),
            'message_ar' => 'تم استلام تقرير الطوارئ. سيتم التواصل معك قريباً.',
        ], 'تم الإبلاغ عن حالة الطوارئ', 201);
    }

    public function contacts(Request $request): JsonResponse
    {
        $cityId = $request->integer('city_id') ?: null;

        return $this->success($this->emergencyService->getEmergencyContacts($cityId));
    }

    public function shareLocation(ShareLocationRequest $request): JsonResponse
    {
        $duration = $request->integer('duration_minutes') ?: 60;

        $share = $this->emergencyService->startLocationShare(
            $request->user(),
            $request->validated('booking_id'),
            (array) $request->validated('recipient_phones', []),
            $request->validated('message_to_recipients'),
            $duration,
        );

        return $this->success([
            'share_id' => $share->id,
            'share_token' => $share->share_token,
            'expires_at' => $share->expires_at?->toIso8601String(),
            'tracking_url' => url("/track/{$share->share_token}"),
            'message_ar' => "تم بدء مشاركة الموقع. سينتهي تلقائياً بعد {$duration} دقيقة.",
        ], 'تم تفعيل مشاركة الموقع', 201);
    }

    public function safetyGuide(): JsonResponse
    {
        return $this->success($this->emergencyService->getSafetyGuide());
    }

    private function getEstimatedResponseTime(string $severity): int
    {
        return match ($severity) {
            'critical' => 5,
            'high' => 15,
            'medium' => 30,
            'low' => 60,
            default => 30,
        };
    }
}
