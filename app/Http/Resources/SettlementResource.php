<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'club_id'        => $this->club_id,
            'period_from'    => $this->period_from,
            'period_to'      => $this->period_to,
            'total_bookings' => $this->total_bookings,
            'total_revenue'  => $this->total_revenue,
            'commission'     => $this->commission,
            'payout_amount'  => $this->payout_amount,
            'status'         => $this->status,
            'paid_at'        => $this->paid_at?->toISOString(),
            'club'           => new ClubResource($this->whenLoaded('club')),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
