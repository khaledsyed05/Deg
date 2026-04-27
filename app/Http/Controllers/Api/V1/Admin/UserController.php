<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->string('search')->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->value()) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($status = $request->string('status')->value()) {
            $query->where('account_status', $status);
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));
        $users = $query->latest()->paginate($perPage);

        return $this->success([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles'])
            ->withCount(['bookings', 'reviews'])
            ->findOrFail($id);

        return $this->success([
            'user' => $user,
            'wallet' => $user->wallet,
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|nullable|email|max:255',
            'account_status' => 'sometimes|in:active,blocked,suspended',
        ]);

        $before = $user->only(array_keys($data));
        $user->update($data);

        AuditLog::record('user.updated', $request->user()->id, $user, ['before' => $before, 'after' => $data], $request->ip());

        return $this->success($user->fresh());
    }

    public function ban(int $id, Request $request): JsonResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $user = User::findOrFail($id);

        $user->update([
            'account_status' => 'blocked',
            'blocked_at' => now(),
            'block_reason' => $reason,
        ]);
        $user->tokens()->delete();

        AuditLog::record('user.banned', $request->user()->id, $user, ['reason' => $reason], $request->ip());

        return $this->success(['user_id' => $user->id, 'status' => 'blocked'], 'تم حظر المستخدم');
    }

    public function unban(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'account_status' => 'active',
            'unblocked_at' => now(),
            'block_reason' => null,
        ]);

        AuditLog::record('user.unbanned', $request->user()->id, $user, [], $request->ip());

        return $this->success(['user_id' => $user->id, 'status' => 'active'], 'تم رفع الحظر');
    }
}
