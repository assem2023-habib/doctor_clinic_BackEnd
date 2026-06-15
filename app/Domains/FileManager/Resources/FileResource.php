<?php

namespace App\Domains\FileManager\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'file_category' => $this->file_category?->value,
            'upload_status' => $this->upload_status?->value,
            'disk' => $this->disk?->value,
            'checksum' => $this->checksum,
            'medical_record_id' => $this->medical_record_id,
            'user_id' => $this->user_id,
            'downloads_count' => $this->when($this->downloads_count !== null, $this->downloads_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
