<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value settings store. Each row = one setting.
 * `payload` is JSON: {"value": <any>, "type": "string|int|bool|float|array"}.
 */
class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'locked' => 'boolean',
        ];
    }

    /** Convenience: decoded runtime value. */
    public function getValueAttribute(): mixed
    {
        $payload = $this->payload ?? [];
        $raw = $payload['value'] ?? null;
        $type = $payload['type'] ?? 'string';

        return self::cast($raw, $type);
    }

    /** Convenience: the declared type for this setting. */
    public function getTypeAttribute(): string
    {
        return $this->payload['type'] ?? 'string';
    }

    public static function cast(mixed $raw, string $type): mixed
    {
        return match ($type) {
            'bool' => (bool) $raw,
            'int' => $raw === null || $raw === '' ? null : (int) $raw,
            'float' => $raw === null || $raw === '' ? null : (float) $raw,
            'array' => is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?? []) : []),
            default => $raw === null ? null : (string) $raw,
        };
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        $row = static::where('name', $name)->first();
        if (! $row) {
            return $default;
        }

        return $row->value ?? $default;
    }

    public static function put(string $name, mixed $value, string $type = 'string', ?string $group = null, bool $locked = false): Setting
    {
        $existing = static::where('name', $name)->first();
        $payload = ['value' => $value, 'type' => $type];

        if ($existing) {
            $existing->update([
                'payload' => $payload,
                'group' => $group ?? $existing->group,
                'locked' => $locked,
            ]);

            return $existing;
        }

        return static::create([
            'name' => $name,
            'group' => $group,
            'payload' => $payload,
            'locked' => $locked,
        ]);
    }
}
