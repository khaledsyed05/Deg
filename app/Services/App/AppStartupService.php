<?php

namespace App\Services\App;

use App\Models\App\MaintenanceWindow;
use App\Models\AppPlatform;
use Illuminate\Support\Facades\Cache;

class AppStartupService
{
    private const CACHE_TTL_SECONDS = 60;

    private const FALLBACK_BASE_URL = 'https://yallaehjez.com/api/v1';

    public function resolve(string $platformKey, string $appVersion): array
    {
        $cacheKey = "app_startup:{$platformKey}:{$appVersion}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($platformKey, $appVersion) {
            return $this->build($platformKey, $appVersion);
        });
    }

    private function build(string $platformKey, string $appVersion): array
    {
        $platform = AppPlatform::where('platform_key', $platformKey)
            ->where('is_active', true)
            ->with('activeEnvironment')
            ->first();

        if (! $platform || ! $platform->activeEnvironment) {
            return $this->fallback($appVersion, 'Platform missing or no active environment');
        }

        $verdict = $this->computeVersionVerdict(
            $appVersion,
            $platform->latest_version,
            $platform->minimum_required_version,
        );

        $isAndroid = $platform->platform_key === 'android';
        $updateUrl = ($isAndroid && $platform->direct_apk_enabled && $platform->direct_apk_url)
            ? $platform->direct_apk_url
            : $platform->store_url;

        $maintenance = MaintenanceWindow::current();
        $maintenanceMode = (bool) $maintenance;

        return [
            'base_url' => $platform->activeEnvironment->base_url,
            'update' => [
                'is_required' => $verdict['is_required'],
                'is_optional' => $verdict['is_optional'],
                'latest_version' => $platform->latest_version,
                'minimum_required_version' => $platform->minimum_required_version,
                'store_url' => $updateUrl,
                'direct_apk_url' => $platform->direct_apk_url,
            ],
            'app_settings' => [
                'maintenance_mode' => $maintenanceMode,
                'maintenance_message' => [
                    'en' => $maintenance?->message,
                    'ar' => $maintenance?->message_ar,
                ],
            ],
        ];
    }

    /**
     * @return array{is_required: bool, is_optional: bool}
     */
    private function computeVersionVerdict(string $appVersion, string $latest, string $minimum): array
    {
        $current = $this->normalize($appVersion);
        $latestN = $this->normalize($latest);
        $minimumN = $this->normalize($minimum);

        if (version_compare($current, $minimumN, '<')) {
            return ['is_required' => true, 'is_optional' => false];
        }

        if (version_compare($current, $latestN, '<')) {
            return ['is_required' => false, 'is_optional' => true];
        }

        return ['is_required' => false, 'is_optional' => false];
    }

    private function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    /**
     * @return array<string, mixed>
     */
    private function fallback(string $appVersion, string $reason): array
    {
        return [
            'base_url' => self::FALLBACK_BASE_URL,
            'update' => [
                'is_required' => false,
                'is_optional' => false,
                'latest_version' => $appVersion,
                'minimum_required_version' => $appVersion,
                'store_url' => null,
                'direct_apk_url' => null,
            ],
            'app_settings' => [
                'maintenance_mode' => false,
                'maintenance_message' => ['en' => null, 'ar' => null],
                '_fallback_reason' => $reason,
            ],
        ];
    }
}
