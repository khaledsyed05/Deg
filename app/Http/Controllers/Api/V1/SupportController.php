<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Support\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Support\ReplyToTicketRequest;
use App\Http\Traits\ApiResponse;
use App\Models\SupportTicket;
use App\Services\Support\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    use ApiResponse;

    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'category' => 'required|in:booking_issue,payment_issue,technical,general',
            'description' => 'required|string|max:2000',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'],
        ]);

        return $this->success($ticket, 'تم إنشاء التذكرة بنجاح', 201);
    }

    public function myTickets(): JsonResponse
    {
        $tickets = auth()->user()->supportTickets()
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    public function faq(): JsonResponse
    {
        $faqs = [
            [
                'question' => 'كيف أقوم بالحجز؟',
                'answer' => 'اختر الملعب، حدد الوقت، ادفع وأكمل الحجز',
            ],
            [
                'question' => 'ما هي طرق الدفع المتاحة؟',
                'answer' => 'Syriatel Cash, MTN Cash, تحويل بنكي، أو نقداً عند الوصول',
            ],
            [
                'question' => 'كيف ألغي الحجز؟',
                'answer' => 'من صفحة الحجز، اضغط على زر إلغاء الحجز. قد تطبق رسوم إلغاء.',
            ],
            [
                'question' => 'متى أستلم المبلغ المسترد؟',
                'answer' => 'خلال 3-5 أيام عمل بعد تأكيد الإلغاء.',
            ],
        ];

        return $this->success($faqs);
    }

    public function submitFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:bug,feature,improvement',
            'message' => 'required|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        activity()
            ->causedBy(auth()->user())
            ->event('app_feedback')
            ->withProperties($validated)
            ->log('User submitted app feedback');

        return $this->success(null, 'شكراً على ملاحظاتك');
    }

    public function showTicket(int $id, TicketService $service): JsonResponse
    {
        return $this->success($service->getTicketDetails($id, auth()->user()));
    }

    public function replyTicket(int $id, ReplyToTicketRequest $request, TicketService $service): JsonResponse
    {
        try {
            $msg = $service->replyToTicket(
                $id,
                $request->user(),
                $request->validated('message'),
                $request->validated('attachments', []) ?? [],
            );
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), null, $e->statusCode);
        }

        return $this->success([
            'message_id' => $msg->id,
            'ticket_status' => $msg->ticket->fresh()->status,
            'ticket_last_activity' => $msg->ticket->fresh()->last_activity_at?->toIso8601String(),
        ], 'تم إرسال الرد بنجاح', 201);
    }
}
