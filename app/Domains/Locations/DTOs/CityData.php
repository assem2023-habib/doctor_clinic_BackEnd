<?php

namespace App\Domains\Locations\DTOs;

use App\Domains\Locations\Requests\StoreCityRequest;
use App\Domains\Locations\Requests\UpdateCityRequest;

class CityData
{
    private function __construct(
        public readonly string $nameAr,
        public readonly string $nameEn,
        public readonly string $countryId,
    ) {}

    public static function fromStoreRequest(StoreCityRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            countryId: $request->country_id,
        );
    }

    public static function fromUpdateRequest(UpdateCityRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            countryId: $request->country_id,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => [
                'ar' => $this->nameAr,
                'en' => $this->nameEn,
            ],
            'country_id' => $this->countryId,
        ];
    }
}
