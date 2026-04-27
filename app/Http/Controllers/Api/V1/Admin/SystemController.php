<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AdminBroadcast;
use App\Models\App\FeatureFlag;
use App\Models\App\MaintenanceWindow;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    use ApiResponse;

    public function toggleMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'message_ar' => 'sometimes|nullable|string|max:500',
            'message_en' => 'sometimes|nullable|string|max:500',
            'ends_at' => 'sometimes|nullable|date',
        ]);

        if ($data['enabled']) {
            $window = MaintenanceWindow::create([
                'starts_at' => now(),
                'ends_at' => $data['ends_at'] ?? now()->addHours(2),
                'message_ar' => $data['message_ar'] ?? 'الموقع تحت الصيانة',
                'message_en' => $data['message_en'] ?? 'Site under maintenance',
                'is_active' => true,
            ]);

            AuditLog::record('maintenance.enabled', $request->user()->id, $window, [], $request->ip());

            return $this->success(['enabled' => true, 'window_id' => $window->id], 'تم تفعيل وضع الصيانة');
        }

        MaintenanceWindow::where('is_active', true)->update(['is_active' => false, 'ends_at' => now()]);
        AuditLog::record('maintenance.disabled', $request->user()->id, null, [], $request->ip());

        return $this->success(['enabled' => false], 'تم إيقاف وضع الصيانة');
    }

    public function toggleFeatureFlag(string $key, Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $flag = FeatureFlag::firstOrNew(['key' => $key]);
        $flag->is_enabled = $data['enabled'];
        $flag->save();

        AuditLog::record('feature_flag.toggled', $request->user()->id, $flag, ['enabled' => $data['enabled']], $request->ip());

        return $this->success(['key' => $key, 'enabled' => $flag->is_enabled]);
    }

    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'message_ar' => 'required|string|max:2000',
            'target_segment' => 'required|in:all_users,active_users,inactive_users,specific_city,specific_users',
            'target_filters' => 'sometimes|nullable|array',
            'target_user_ids' => 'sometimes|nullable|array',
            'target_user_ids.*' => 'integer|exists:users,id',
            'scheduled_at' => 'sometimes|nullable|date',
        ]);

        $userIds = $this->resolveTargetUsers($data);

        $broadcast = AdminBroadcast::create([
            'sent_by' => $request->user()->id,
            'title' => $data['title'],
            'title_ar' => $data['title_ar'],
            'message' => $data['message'],
            'message_ar' => $data['message_ar'],
            'target_segment' => $data['target_segment'],
            'target_filters' => $data['target_filters'] ?? null,
            'target_user_ids' => $data['target_user_ids'] ?? null,
            'recipients_count' => count($userIds),
            'status' => empty($data['scheduled_at']) ? 'sent' : 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'sent_at' => empty($data['scheduled_at']) ? now() : null,
        ]);

        if (empty($data['scheduled_at']) && ! empty($userIds)) {
            $payload = [
                'type' => 'admin_broadcast',
                'broadcast_id' => $broadcast->id,
                'title_ar' => $data['title_ar'],
                'message_ar' => $data['message_ar'],
            ];
            $now = now();
            $rows = array_map(fn ($uid) => [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\AdminBroadcastNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $uid,
                'data' => json_encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ], $userIds);
            DB::table('notifications')->insert($rows);
        }

        AuditLog::record('broadcast.sent', $request->user()->id, $broadcast, ['recipients' => count($userIds)], $request->ip());

        return $this->success([
            'broadcast_id' => $broadcast->id,
            'recipients_count' => count($userIds),
            'status' => $broadcast->status,
        ], 'تم إرسال البث', 201);
    }

    public function auditLog(Request $request): JsonResponse
    {
        $query = AuditLog::query()->with(['user:id,name']);

        if ($action = $request->string('action')->value()) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest()->paginate(50);

        return $this->success([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    private function resolveTargetUsers(array $data): array
    {
        return match ($data['target_segment']) {
            'all_users' => User::pluck('id')->all(),
            'active_users' => User::where('account_status', 'active')->pluck('id')->all(),
            'inactive_users' => User::where('account_status', '!=', 'active')->pluck('id')->all(),
            'specific_city' => User::where('default_city_id', $data['target_filters']['city_id'] ?? 0)->pluck('id')->all(),
            'specific_users' => $data['target_user_ids'] ?? [],
        };
    }
}
