<?php

namespace App\Domains\Images\DTOs;

use App\Enums\ImageTypeEnum;
use Illuminate\Http\UploadedFile;

class UploadImageData
{
    private function __construct(
        public readonly UploadedFile $file,
        public readonly ImageTypeEnum $type,
        public readonly string $imageableId,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            file: $validated['file'],
            type: ImageTypeEnum::from($validated['type']),
            imageableId: $validated['imageable_id'],
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            type: $data['type'],
            imageableId: $data['imageable_id'],
        );
    }
}
