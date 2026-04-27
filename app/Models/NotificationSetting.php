<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Per-type defaults. Promotional types are opt-in.
     *
     * @return array<string, array<string, bool>>
     */
    public static function getDefaults(): array
    {
        $defaults = [];

        foreach (NotificationType::cases() as $type) {
            $defaults[$type->value] = [
                'enabled' => ! $type->isPromotional(),
                'push_enabled' => ! $type->isPromotional(),
                'sms_enabled' => false,
                'email_enabled' => false,
            ];
        }

        return $defaults;
    }

    public static function createDefaultsForUser(int $userId): void
    {
        foreach (self::getDefaults() as $type => $values) {
            self::firstOrCreate(
                ['user_id' => $userId, 'notification_type' => $type],
                $values,
            );
        }
    }

    public function shouldSend(string $channel = 'push'): bool
    {
        if (! $this->enabled) {
            return false;
        }

        return match ($channel) {
            'push' => (bool) $this->push_enabled,
            'sms' => (bool) $this->sms_enabled,
            'email' => (bool) $this->email_enabled,
            default => true,
        };
    }
}
