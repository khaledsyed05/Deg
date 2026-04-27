<?php

namespace App\Http\Resources\V1\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : (array) json_decode((string) $this->data, true);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'data' => $data,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
