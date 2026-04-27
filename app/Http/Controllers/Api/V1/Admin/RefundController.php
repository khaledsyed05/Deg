<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\CreditType;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\RefundRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    use ApiResponse;

    public function pending(): JsonResponse
    {
        $requests = RefundRequest::where('status', 'pending_review')
            ->with(['booking:id,booking_code,total_price,venue_id', 'user:id,name,phone_number'])
            ->latest()
            ->paginate(20);

        return $this->success([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'adjusted_amount' => 'sometimes|nullable|numeric|min:0',
        ]);

        $refund = RefundRequest::where('id', $id)
            ->where('status', 'pending_review')
            ->firstOrFail();

        $approved = isset($data['adjusted_amount']) ? (float) $data['adjusted_amount'] : (float) $refund->requested_amount;

        DB::transaction(function () use ($refund, $approved, $request) {
            $refund->update([
                'status' => 'approved',
                'approved_amount' => $approved,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $wallet = $refund->user->walletOrCreate();
            $wallet->credit(
                (int) $approved,
                CreditType::REFUND,
                "Manual refund approval for booking #{$refund->booking_id}",
                $refund,
            );

            $refund->update(['status' => 'completed', 'processed_at' => now()]);
        });

        AuditLog::record('refund.approved', $request->user()->id, $refund, ['amount' => $approved], $request->ip());

        return $this->success(['refund_id' => $refund->id, 'status' => 'completed', 'approved_amount' => $approved], 'تمت الموافقة على الاسترداد');
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];

        $refund = RefundRequest::where('id', $id)
            ->where('status', 'pending_review')
            ->firstOrFail();

        $refund->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record('refund.rejected', $request->user()->id, $refund, ['reason' => $reason], $request->ip());

        return $this->success(['refund_id' => $refund->id, 'status' => 'rejected'], 'تم رفض طلب الاسترداد');
    }
}
