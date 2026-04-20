<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'booking_id'      => $this->booking_id,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'provider'        => $this->provider,
            'flow_type'       => $this->flow_type,
            'status'          => $this->status,
            'reference'       => $this->reference,
            'initiated_at'    => $this->initiated_at?->toISOString(),
            'confirmed_at'    => $this->confirmed_at?->toISOString(),
            'failed_at'       => $this->failed_at?->toISOString(),
        ];
    }
}
