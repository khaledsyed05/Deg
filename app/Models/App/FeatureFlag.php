<?php

namespace App\Models\App;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'targeting' => 'array',
        ];
    }

    public function isEnabledFor(?User $user = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (empty($this->targeting)) {
            return true;
        }

        if (isset($this->targeting['user_ids']) && is_array($this->targeting['user_ids'])) {
            if ($user && in_array($user->id, $this->targeting['user_ids'], true)) {
                return true;
            }
            if (! isset($this->targeting['percentage'])) {
                return false;
            }
        }

        if (isset($this->targeting['percentage'])) {
            if (! $user) {
                return false;
            }
            $hash = crc32($user->id.$this->key) % 100;

            return $hash < (int) $this->targeting['percentage'];
        }

        return false;
    }
}
