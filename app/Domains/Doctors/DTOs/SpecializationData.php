<?php

namespace App\Domains\Doctors\DTOs;

use App\Domains\Doctors\Requests\StoreSpecializationRequest;
use App\Domains\Doctors\Requests\UpdateSpecializationRequest;
use Illuminate\Support\Str;

class SpecializationData
{
    private function __construct(
        public readonly string $nameAr,
        public readonly string $nameEn,
        public readonly ?string $descriptionAr,
        public readonly ?string $descriptionEn,
        public readonly bool $isActive,
    ) {}

    public static function fromStoreRequest(StoreSpecializationRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            descriptionAr: $request->description_ar,
            descriptionEn: $request->description_en,
            isActive: $request->boolean('is_active', true),
        );
    }

    public static function fromUpdateRequest(UpdateSpecializationRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            descriptionAr: $request->description_ar,
            descriptionEn: $request->description_en,
            isActive: $request->boolean('is_active', true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => [
                'ar' => $this->nameAr,
                'en' => $this->nameEn,
            ],
            'slug' => Str::slug($this->nameEn),
            'description' => $this->descriptionAr || $this->descriptionEn
                ? [
                    'ar' => $this->descriptionAr ?? '',
                    'en' => $this->descriptionEn ?? '',
                ]
                : null,
            'is_active' => $this->isActive,
        ];
    }
}
