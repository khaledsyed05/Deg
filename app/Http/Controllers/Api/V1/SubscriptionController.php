<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Subscription\CreateSubscriptionRequest;
use App\Http\Requests\Api\V1\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Traits\ApiResponse;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(): JsonResponse
    {
        $items = auth()->user()->subscriptions()
            ->with('venue')
            ->latest()
            ->paginate(20);

        return SubscriptionResource::collection($items)->response();
    }

    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptions->create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return $this->success(
            new SubscriptionResource($subscription->load('venue')),
            'تم إنشاء الاشتراك',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);

        return $this->success(new SubscriptionResource($subscription->load('venue')));
    }

    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);

        if (in_array($subscription->status, ['cancelled', 'expired'], true)) {
            return $this->error('لا يمكن تعديل اشتراك منتهٍ أو ملغى', null, 422);
        }

        $subscription->update($request->validated());

        return $this->success(new SubscriptionResource($subscription->fresh('venue')), 'تم تحديث الاشتراك');
    }

    public function pause(int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);
        $result = $subscription->pause();

        if (! $result['success']) {
            return $this->error($result['message'], null, 422);
        }

        return $this->success(new SubscriptionResource($subscription->fresh('venue')), $result['message']);
    }

    public function resume(int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);
        $result = $subscription->resume();

        if (! $result['success']) {
            return $this->error($result['message'], null, 422);
        }

        $this->subscriptions->createInstancesForNext7Days($subscription->fresh());

        return $this->success(new SubscriptionResource($subscription->fresh('venue')), $result['message']);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $subscription = $this->findOwned($id);

        if (in_array($subscription->status, ['cancelled', 'expired'], true)) {
            return $this->error('الاشتراك ملغى أو منتهٍ بالفعل', null, 422);
        }

        $subscription->cancel($request->input('reason'));

        return $this->success(new SubscriptionResource($subscription->fresh('venue')), 'تم إلغاء الاشتراك');
    }

    public function history(int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);

        $bookings = $subscription->bookings()
            ->with('venue')
            ->latest('booking_date')
            ->paginate(20);

        return response()->json($bookings);
    }

    public function upcoming(int $id): JsonResponse
    {
        $subscription = $this->findOwned($id);

        $instances = $subscription->instances()
            ->whereIn('status', ['scheduled', 'created'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time')
            ->get();

        return $this->success([
            'subscription_id' => $subscription->id,
            'instances' => $instances->map(fn ($instance) => [
                'id' => $instance->id,
                'scheduled_date' => $instance->scheduled_date->toDateString(),
                'scheduled_start_time' => substr((string) $instance->scheduled_start_time, 0, 5),
                'scheduled_end_time' => substr((string) $instance->scheduled_end_time, 0, 5),
                'status' => $instance->status,
                'payment_status' => $instance->payment_status,
                'booking_id' => $instance->booking_id,
            ]),
        ]);
    }

    public function skipInstance(Request $request, int $id, int $instanceId): JsonResponse
    {
        $subscription = $this->findOwned($id);

        $instance = $subscription->instances()->findOrFail($instanceId);

        if ($instance->status !== 'scheduled') {
            return $this->error('لا يمكن تخطي هذا الموعد', null, 422);
        }

        $instance->skip($request->input('reason', 'user_skipped'));

        return $this->success(null, 'تم تخطي الموعد');
    }

    public function nextCharges(): JsonResponse
    {
        $charges = auth()->user()->subscriptions()
            ->active()
            ->whereNotNull('next_charge_date')
            ->with('venue')
            ->orderBy('next_charge_date')
            ->get()
            ->map(fn ($subscription) => [
                'subscription_id' => $subscription->id,
                'venue' => [
                    'id' => $subscription->venue->id,
                    'name' => $subscription->venue->name,
                ],
                'next_charge_date' => $subscription->next_charge_date?->toDateString(),
                'next_booking_date' => $subscription->next_booking_date?->toDateString(),
                'amount' => (int) $subscription->price_per_booking,
                'currency' => 'SYP',
            ])
            ->values();

        return $this->success(['charges' => $charges]);
    }

    public function updatePaymentMethod(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'auto_pay' => ['sometimes', 'boolean'],
        ]);

        $subscription = $this->findOwned($id);

        $subscription->update([
            'payment_method_id' => $request->integer('payment_method_id'),
            'auto_pay' => $request->boolean('auto_pay', $subscription->auto_pay),
        ]);

        return $this->success(new SubscriptionResource($subscription->fresh('venue')), 'تم تحديث طريقة الدفع');
    }

    protected function findOwned(int $id): Subscription
    {
        return Subscription::where('user_id', auth()->id())->findOrFail($id);
    }
}
