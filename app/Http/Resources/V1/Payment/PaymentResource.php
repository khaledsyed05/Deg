<?php

namespace App\Http\Resources\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'booking_code' => $this->booking?->booking_code,
            'amount' => (int) $this->amount,
            'currency' => $this->currency ?? 'SYP',
            'provider' => $this->provider?->value,
            'flow_type' => $this->flow_type?->value,
            'status' => $this->status?->value,
            'provider_reference' => $this->provider_reference,
            'provider_transaction_id' => $this->provider_transaction_id,
            'initiated_at' => $this->initiated_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'refunded_at' => $this->refunded_at?->toISOString(),
        ];
    }
}
