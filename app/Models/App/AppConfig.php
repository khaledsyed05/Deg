<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppConfig extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("app_config_{$key}", 3600, function () use ($key, $default) {
            $config = self::where('key', $key)->first();

            return $config ? $config->value : $default;
        });
    }

    public static function setValue(string $key, mixed $value, bool $isPublic = false): self
    {
        Cache::forget("app_config_{$key}");
        Cache::forget('app_configs_public');

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_public' => $isPublic]
        );
    }
}
