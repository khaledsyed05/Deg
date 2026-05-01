<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\App\AppVersion;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppVersionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = AppVersion::query()->orderBy('platform')->orderByDesc('build_number');

        if ($platform = $request->string('platform')->value()) {
            $query->where('platform', $platform);
        }

        $versions = $query->paginate(20);

        return $this->success([
            'data' => $versions->items(),
            'meta' => [
                'current_page' => $versions->currentPage(),
                'last_page' => $versions->lastPage(),
                'total' => $versions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => 'required|in:ios,android',
            'version' => ['required', 'string', 'max:20', Rule::unique('app_versions', 'version')->where('platform', $request->platform)],
            'build_number' => 'required|integer|min:1',
            'is_force_update' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'release_notes' => 'sometimes|nullable|string|max:5000',
            'release_notes_ar' => 'sometimes|nullable|string|max:5000',
            'released_at' => 'required|date',
        ]);

        $version = AppVersion::create($data);

        AuditLog::record('app_version.created', $request->user()->id, $version, [], $request->ip());

        return $this->success($version, 'تم إنشاء الإصدار', 201);
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(AppVersion::findOrFail($id));
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $version = AppVersion::findOrFail($id);

        $data = $request->validate([
            'platform' => 'sometimes|in:ios,android',
            'version' => ['sometimes', 'string', 'max:20', Rule::unique('app_versions', 'version')->where('platform', $request->platform ?? $version->platform)->ignore($id)],
            'build_number' => 'sometimes|integer|min:1',
            'is_force_update' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'release_notes' => 'sometimes|nullable|string|max:5000',
            'release_notes_ar' => 'sometimes|nullable|string|max:5000',
            'released_at' => 'sometimes|date',
        ]);

        $before = $version->only(array_keys($data));
        $version->update($data);

        AuditLog::record('app_version.updated', $request->user()->id, $version, ['before' => $before, 'after' => $data], $request->ip());

        return $this->success($version->fresh(), 'تم تحديث الإصدار');
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $version = AppVersion::findOrFail($id);
        $version->delete();

        AuditLog::record('app_version.deleted', $request->user()->id, null, ['id' => $id], $request->ip());

        return $this->success(['id' => $id, 'deleted' => true], 'تم حذف الإصدار');
    }

    public function activate(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $version = AppVersion::findOrFail($id);
        $version->update(['is_active' => $data['is_active']]);

        $action = $data['is_active'] ? 'app_version.activated' : 'app_version.deactivated';
        AuditLog::record($action, $request->user()->id, $version, ['is_active' => $data['is_active']], $request->ip());

        return $this->success(['id' => $version->id, 'is_active' => $version->is_active]);
    }
}
