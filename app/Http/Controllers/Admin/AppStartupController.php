<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App\MaintenanceWindow;
use App\Models\AppEnvironment;
use App\Models\AppPlatform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppStartupController extends Controller
{
    public function index(): Response
    {
        $platforms = AppPlatform::query()
            ->with(['environments' => fn ($q) => $q->orderBy('id')])
            ->orderBy('order_column')
            ->get()
            ->map(fn (AppPlatform $p) => [
                'id' => $p->id,
                'platform_key' => $p->platform_key,
                'name' => $p->getTranslations('name'),
                'latest_version' => $p->latest_version,
                'minimum_required_version' => $p->minimum_required_version,
                'store_url' => $p->store_url,
                'direct_apk_url' => $p->direct_apk_url,
                'direct_apk_enabled' => (bool) $p->direct_apk_enabled,
                'is_active' => (bool) $p->is_active,
                'order_column' => $p->order_column,
                'environments' => $p->environments->map(fn (AppEnvironment $e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'base_url' => $e->base_url,
                    'is_active' => (bool) $e->is_active,
                ])->values(),
            ]);

        $maintenance = MaintenanceWindow::query()->latest('id')->first();

        return Inertia::render('Admin/AppStartup/Index', [
            'platforms' => $platforms,
            'maintenance' => $maintenance ? [
                'id' => $maintenance->id,
                'is_active' => (bool) $maintenance->is_active,
                'message' => $maintenance->message,
                'message_ar' => $maintenance->message_ar,
                'starts_at' => $maintenance->starts_at?->toIso8601String(),
                'ends_at' => $maintenance->ends_at?->toIso8601String(),
            ] : null,
        ]);
    }

    // ─── PLATFORMS ────────────────────────────────────────────

    public function updatePlatform(Request $request, AppPlatform $platform): RedirectResponse
    {
        $data = $request->validate([
            'latest_version' => ['required', 'regex:/^v?\d+\.\d+\.\d+(\.\d+)?$/'],
            'minimum_required_version' => ['required', 'regex:/^v?\d+\.\d+\.\d+(\.\d+)?$/'],
            'store_url' => ['required', 'url', 'max:500'],
            'direct_apk_url' => ['nullable', 'url', 'max:500'],
            'direct_apk_enabled' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (version_compare(ltrim($data['minimum_required_version'], 'vV'), ltrim($data['latest_version'], 'vV'), '>')) {
            return back()->withErrors([
                'minimum_required_version' => 'Minimum required version cannot exceed latest version.',
            ]);
        }

        $before = $platform->only(array_keys($data));
        $platform->update(array_merge($data, ['last_updated_by' => $request->user()?->id]));

        activity('app_startup')
            ->causedBy($request->user())
            ->performedOn($platform)
            ->withProperties(['before' => $before, 'after' => $data])
            ->log('app_platform_updated');

        $this->clearCache();

        return back()->with('success', __('App startup config saved. Mobile clients pick up changes within 60 seconds.'));
    }

    // ─── ENVIRONMENTS ─────────────────────────────────────────

    public function storeEnvironment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_id' => ['required', 'exists:app_platforms,id'],
            'name' => ['required', 'string', 'max:50'],
            'base_url' => ['required', 'url', 'max:500'],
        ]);

        $exists = AppEnvironment::where('platform_id', $data['platform_id'])
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'An environment with this name already exists for this platform.']);
        }

        $env = AppEnvironment::create(array_merge($data, ['is_active' => 0]));

        activity('app_startup')
            ->causedBy($request->user())
            ->performedOn($env)
            ->withProperties($data)
            ->log('app_environment_created');

        $this->clearCache();

        return back()->with('success', __('Environment added.'));
    }

    public function updateEnvironment(Request $request, AppEnvironment $environment): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'base_url' => ['sometimes', 'url', 'max:500'],
        ]);

        $before = $environment->only(array_keys($data));
        $environment->update($data);

        activity('app_startup')
            ->causedBy($request->user())
            ->performedOn($environment)
            ->withProperties(['before' => $before, 'after' => $data])
            ->log('app_environment_updated');

        $this->clearCache();

        return back()->with('success', __('App startup config saved. Mobile clients pick up changes within 60 seconds.'));
    }

    public function activateEnvironment(Request $request, AppEnvironment $environment): RedirectResponse
    {
        DB::transaction(function () use ($environment) {
            AppEnvironment::where('platform_id', $environment->platform_id)
                ->update(['is_active' => 0]);
            $environment->update(['is_active' => 1]);
        });

        activity('app_startup')
            ->causedBy($request->user())
            ->performedOn($environment)
            ->log('app_environment_activated');

        $this->clearCache();

        return back()->with('success', __('Environment activated.'));
    }

    public function destroyEnvironment(Request $request, AppEnvironment $environment): RedirectResponse
    {
        if ($environment->is_active) {
            return back()->withErrors([
                'environment' => 'Cannot delete the active environment. Activate another one first.',
            ]);
        }

        $environment->delete();

        activity('app_startup')
            ->causedBy($request->user())
            ->withProperties(['id' => $environment->id, 'name' => $environment->name])
            ->log('app_environment_deleted');

        $this->clearCache();

        return back()->with('success', __('Environment deleted.'));
    }

    // ─── MAINTENANCE WINDOW ───────────────────────────────────

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
            'message_ar' => ['nullable', 'string', 'max:1000'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $window = MaintenanceWindow::latest('id')->first();
        $payload = [
            'is_active' => $data['is_active'],
            'message' => $data['message'] ?? null,
            'message_ar' => $data['message_ar'] ?? null,
            'starts_at' => $data['is_active'] ? now() : null,
            'ends_at' => $data['ends_at'] ?? null,
        ];

        if ($window) {
            $before = $window->only(array_keys($payload));
            $window->update($payload);
        } else {
            $window = MaintenanceWindow::create($payload);
            $before = null;
        }

        activity('app_startup')
            ->causedBy($request->user())
            ->performedOn($window)
            ->withProperties(['before' => $before, 'after' => $payload])
            ->log('app_maintenance_updated');

        $this->clearCache();

        return back()->with('success', __('Maintenance settings saved.'));
    }

    private function clearCache(): void
    {
        Cache::flush();
    }
}
