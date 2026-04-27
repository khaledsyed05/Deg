<?php

namespace App\Services\App;

use App\Models\App\AppConfig;
use App\Models\App\AppVersion;
use App\Models\App\FeatureFlag;
use App\Models\App\MaintenanceWindow;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppMetadataService
{
    public function checkVersion(string $platform, string $currentVersion, int $currentBuild): array
    {
        $latest = AppVersion::forPlatform($platform)->orderByDesc('build_number')->first();
        $minimum = AppVersion::forPlatform($platform)
            ->where('is_force_update', true)
            ->orderByDesc('build_number')
            ->first();

        if (! $latest) {
            return [
                'current_version' => $currentVersion,
                'current_build' => $currentBuild,
                'needs_update' => false,
                'force_update' => false,
            ];
        }

        return [
            'current_version' => $latest->version,
            'current_build' => $latest->build_number,
            'minimum_version' => $minimum?->version,
            'minimum_build' => $minimum?->build_number,
            'needs_update' => $currentBuild < $latest->build_number,
            'force_update' => (bool) ($minimum && $currentBuild < $minimum->build_number),
            'release_notes' => $latest->release_notes,
            'release_notes_ar' => $latest->release_notes_ar,
            'store_url' => $platform === 'ios'
                ? config('app.ios_store_url')
                : config('app.android_store_url'),
            'released_at' => $latest->released_at?->format('Y-m-d'),
        ];
    }

    public function getFeatureFlags(?User $user = null): array
    {
        $cacheKey = $user ? "feature_flags_user_{$user->id}" : 'feature_flags_anon';

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return FeatureFlag::all()
                ->mapWithKeys(fn ($flag) => [$flag->key => $flag->isEnabledFor($user)])
                ->toArray();
        });
    }

    public function getMaintenanceStatus(): array
    {
        $window = MaintenanceWindow::current();

        if (! $window) {
            return ['is_under_maintenance' => false];
        }

        $remaining = null;
        if ($window->ends_at) {
            $remaining = max(0, (int) now()->diffInMinutes($window->ends_at, false));
        }

        return [
            'is_under_maintenance' => true,
            'message' => $window->message,
            'message_ar' => $window->message_ar,
            'starts_at' => $window->starts_at?->toIso8601String(),
            'ends_at' => $window->ends_at?->toIso8601String(),
            'estimated_minutes_remaining' => $remaining,
        ];
    }

    public function getPublicConfig(): array
    {
        return Cache::remember('app_configs_public', 3600, function () {
            return AppConfig::where('is_public', true)
                ->get()
                ->mapWithKeys(fn ($c) => [$c->key => $c->value])
                ->toArray();
        });
    }

    public function healthCheck(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'fail';
        }

        try {
            Cache::put('health_check', '1', 5);
            $checks['cache'] = Cache::get('health_check') === '1' ? 'ok' : 'fail';
        } catch (\Throwable) {
            $checks['cache'] = 'fail';
        }

        try {
            $checks['storage'] = is_writable(storage_path()) ? 'ok' : 'fail';
        } catch (\Throwable) {
            $checks['storage'] = 'fail';
        }

        $allOk = ! in_array('fail', $checks, true);

        return [
            'status' => $allOk ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'uptime_seconds' => $this->getUptime(),
        ];
    }

    private function getUptime(): int
    {
        $marker = storage_path('framework/deployed_at');
        if (file_exists($marker)) {
            return time() - (int) file_get_contents($marker);
        }

        return 0;
    }
}
