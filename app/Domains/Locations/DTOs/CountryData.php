<?php

namespace App\Domains\Locations\DTOs;

use App\Domains\Locations\Requests\StoreCountryRequest;
use App\Domains\Locations\Requests\UpdateCountryRequest;

class CountryData
{
    private function __construct(
        public readonly string $nameAr,
        public readonly string $nameEn,
        public readonly string $code,
        public readonly ?string $flag,
    ) {}

    public static function fromStoreRequest(StoreCountryRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            code: $request->code,
            flag: $request->flag,
        );
    }

    public static function fromUpdateRequest(UpdateCountryRequest $request): self
    {
        return new self(
            nameAr: $request->name_ar,
            nameEn: $request->name_en,
            code: $request->code,
            flag: $request->flag,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => [
                'ar' => $this->nameAr,
                'en' => $this->nameEn,
            ],
            'code' => $this->code,
            'flag' => $this->flag,
        ];
    }
}
