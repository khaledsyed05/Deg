<?php

namespace App\Http\Resources\Wallet;

use App\Models\Booking;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'credit_type' => $this->credit_type,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'booking_id' => $this->reference_type === Booking::class
                ? (int) $this->reference_id
                : null,
            'idempotency_key' => $this->idempotency_key,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
