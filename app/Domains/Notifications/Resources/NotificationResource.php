<?php

namespace App\Domains\Notifications\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'title' => $this->title,
            'body' => $this->body,
            'is_read' => !is_null($this->pivot?->read_at),
            'read_at' => $this->pivot?->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
